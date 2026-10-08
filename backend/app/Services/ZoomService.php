<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for interacting with the Zoom API.
 *
 * Uses Server-to-Server OAuth for authentication.
 * Handles creating, updating, and deleting Zoom meetings.
 */
class ZoomService
{
    private string $baseUrl;
    private string $oauthUrl;
    private string $accountId;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->baseUrl      = config('zoom.base_url');
        $this->oauthUrl     = config('zoom.oauth_url');
        $this->accountId    = config('zoom.account_id');
        $this->clientId     = config('zoom.client_id');
        $this->clientSecret = config('zoom.client_secret');
    }

    /**
     * Get an OAuth access token using Server-to-Server OAuth.
     * Token is cached for 55 minutes (Zoom tokens last ~60 min).
     */
    public function getAccessToken(): string
    {
        return Cache::remember('zoom_access_token', 55 * 60, function () {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post($this->oauthUrl, [
                    'grant_type'  => 'account_credentials',
                    'account_id'  => $this->accountId,
                ]);

            if ($response->failed()) {
                Log::error('Zoom OAuth failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                throw new \RuntimeException('Failed to obtain Zoom access token');
            }

            return $response->json('access_token');
        });
    }

    /**
     * Create a Zoom meeting.
     *
     * @param array $data Meeting data: title, description, scheduled_at, duration, timezone
     * @return array Keys: zoom_meeting_id, join_url, start_url, password, zoom_host_id
     */
    public function createMeeting(array $data): array
    {
        $token = $this->getAccessToken();

        $payload = [
            'topic'      => $data['title'],
            'type'       => 2, // Scheduled meeting
            'start_time' => $data['scheduled_at'], // ISO 8601 format
            'duration'   => $data['duration'] ?? 60,
            'timezone'   => $data['timezone'] ?? 'Asia/Kolkata',
            'agenda'     => $data['description'] ?? '',
            'settings'   => [
                'host_video'        => true,
                'participant_video' => true,
                'join_before_host'  => false,
                'mute_upon_entry'   => true,
                'waiting_room'      => true,
                'approval_type'     => 0, // Automatically approve
                'audio'             => 'both',
            ],
        ];

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/users/me/meetings", $payload);

        if ($response->failed()) {
            Log::error('Zoom create meeting failed', [
                'status'  => $response->status(),
                'body'    => $response->body(),
                'payload' => $payload,
            ]);
            throw new \RuntimeException('Failed to create Zoom meeting: ' . $response->body());
        }

        $meeting = $response->json();

        return [
            'zoom_meeting_id' => (string) $meeting['id'],
            'zoom_host_id'    => $meeting['host_id'] ?? null,
            'join_url'        => $meeting['join_url'],
            'start_url'       => $meeting['start_url'],
            'password'        => $meeting['password'] ?? null,
        ];
    }

    /**
     * Update an existing Zoom meeting.
     *
     * @param string $meetingId The Zoom meeting ID
     * @param array  $data      Fields to update: title, description, scheduled_at, duration, timezone
     * @return array Updated meeting data
     */
    public function updateMeeting(string $meetingId, array $data): array
    {
        $token = $this->getAccessToken();

        $payload = [];
        if (isset($data['title']))        $payload['topic']      = $data['title'];
        if (isset($data['description']))  $payload['agenda']     = $data['description'];
        if (isset($data['scheduled_at'])) $payload['start_time'] = $data['scheduled_at'];
        if (isset($data['duration']))     $payload['duration']   = $data['duration'];
        if (isset($data['timezone']))     $payload['timezone']   = $data['timezone'];

        $response = Http::withToken($token)
            ->patch("{$this->baseUrl}/meetings/{$meetingId}", $payload);

        if ($response->failed()) {
            Log::error('Zoom update meeting failed', [
                'status'    => $response->status(),
                'body'      => $response->body(),
                'meetingId' => $meetingId,
            ]);
            throw new \RuntimeException('Failed to update Zoom meeting: ' . $response->body());
        }

        // Fetch updated meeting details
        return $this->getMeeting($meetingId);
    }

    /**
     * Delete a Zoom meeting.
     *
     * @param string $meetingId The Zoom meeting ID
     * @return bool
     */
    public function deleteMeeting(string $meetingId): bool
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->delete("{$this->baseUrl}/meetings/{$meetingId}");

        if ($response->failed()) {
            Log::error('Zoom delete meeting failed', [
                'status'    => $response->status(),
                'body'      => $response->body(),
                'meetingId' => $meetingId,
            ]);
            throw new \RuntimeException('Failed to delete Zoom meeting: ' . $response->body());
        }

        return true;
    }

    /**
     * Get details of a Zoom meeting.
     *
     * @param string $meetingId The Zoom meeting ID
     * @return array Meeting data from Zoom API
     */
    public function getMeeting(string $meetingId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/meetings/{$meetingId}");

        if ($response->failed()) {
            Log::error('Zoom get meeting failed', [
                'status'    => $response->status(),
                'body'      => $response->body(),
                'meetingId' => $meetingId,
            ]);
            throw new \RuntimeException('Failed to fetch Zoom meeting: ' . $response->body());
        }

        $meeting = $response->json();

        return [
            'zoom_meeting_id' => (string) $meeting['id'],
            'zoom_host_id'    => $meeting['host_id'] ?? null,
            'join_url'        => $meeting['join_url'],
            'start_url'       => $meeting['start_url'],
            'password'        => $meeting['password'] ?? null,
            'status'          => $meeting['status'] ?? 'waiting',
        ];
    }
}
