@extends('admin.layout')

@section('title', 'Dashboard')

@section('extra_css')
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin-bottom: 40px;
    }

    .stat-card {
        padding: 30px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        position: relative;
    }

    .stat-icon {
        position: absolute;
        top: 30px;
        right: 30px;
        font-size: 2.5rem;
        opacity: 0.2;
    }

    .stat-title {
        font-size: 1rem;
        color: var(--text-muted);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .stat-value {
        font-size: 3.5rem;
        font-weight: 800;
        line-height: 1;
        background: linear-gradient(135deg, #fff, #94a3b8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .stat-card.danger .stat-value {
        background: linear-gradient(135deg, #fecdd3, #f43f5e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .stat-card.danger .stat-icon {
        color: var(--accent);
        opacity: 0.3;
    }

    .stat-card.primary .stat-icon {
        color: var(--primary);
        opacity: 0.3;
    }

    .stat-footer {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        color: var(--text-muted);
        margin-top: auto;
    }
</style>
@endsection

@section('content')
    <div class="stats-grid">
        <div class="glass-card stat-card primary">
            <i class="ri-user-line stat-icon"></i>
            <div class="stat-title">Total Registered Users</div>
            <div class="stat-value">{{ $totalUsers }}</div>
            <div class="stat-footer">
                <i class="ri-bar-chart-2-line" style="color: var(--secondary)"></i>
                Active accounts on the platform
            </div>
        </div>
        
        <div class="glass-card stat-card {{ $totalViolations > 0 ? 'danger' : '' }}">
            <i class="ri-shield-keyhole-line stat-icon"></i>
            <div class="stat-title">Security Violations</div>
            <div class="stat-value">{{ $totalViolations }}</div>
            <div class="stat-footer">
                @if($totalViolations > 0)
                    <i class="ri-error-warning-line" style="color: var(--accent)"></i>
                    <span style="color: #fb7185">Immediate action recommended</span>
                @else
                    <i class="ri-checkbox-circle-line" style="color: #34d399"></i>
                    <span>System is completely secure</span>
                @endif
            </div>
        </div>
    </div>
    
    <!-- You can add more dashboard widgets here -->
    <div class="glass-card" style="padding: 40px; text-align: center; border-style: dashed; border-color: rgba(255,255,255,0.2)">
        <i class="ri-line-chart-line" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px; display: block"></i>
        <h3 style="margin-bottom: 10px; font-weight: 500">More analytics coming soon</h3>
        <p style="color: var(--text-muted)">The custom admin panel is fully extensible.</p>
    </div>
@endsection
