@extends('admin.layout')

@section('title', 'Manage Users')

@section('extra_css')
<style>
    .data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        text-align: left;
    }

    .data-table th {
        padding: 20px;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 1px;
        border-bottom: 1px solid var(--border);
    }

    .data-table td {
        padding: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        transition: background 0.2s ease;
    }

    .data-table tbody tr:hover td {
        background: rgba(255, 255, 255, 0.03);
    }

    .data-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        color: white;
    }
    
    .user-name {
        font-weight: 600;
        color: var(--text-main);
    }
    
    .user-email {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-top: 2px;
    }
    
    .pagination-container {
        margin-top: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .pagination-container svg {
        width: 20px;
    }
    
    /* override simple pagination colors */
    .pagination-container nav {
        display: flex;
        width: 100%;
        justify-content: space-between;
    }
    
    .pagination-container nav a {
        padding: 10px 20px;
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--border);
        border-radius: 8px;
        color: var(--text-main);
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .pagination-container nav a:hover {
        background: rgba(255,255,255,0.1);
        border-color: var(--border-highlight);
    }
    
    .pagination-container nav span {
        padding: 10px 20px;
        color: var(--text-muted);
        background: transparent;
        border: 1px solid transparent;
    }
</style>
@endsection

@section('content')
    <div class="glass-card" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Security Violations</th>
                        <th>Registered Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="avatar">{{ substr($user->name, 0, 1) }}</div>
                                    <div>
                                        <div class="user-name">{{ $user->name }}</div>
                                        <div class="user-email">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($user->role === 'admin')
                                    <span class="badge badge-primary"><i class="ri-shield-star-line"></i> Admin</span>
                                @else
                                    <span class="badge badge-neutral"><i class="ri-user-smile-line"></i> Student</span>
                                @endif
                            </td>
                            <td>
                                @if($user->security_violations_count > 0)
                                    <span class="badge badge-danger">
                                        <i class="ri-alert-line"></i> {{ $user->security_violations_count }} Violations
                                    </span>
                                @else
                                    <span class="badge badge-success">
                                        <i class="ri-check-line"></i> 0 Violations
                                    </span>
                                @endif
                            </td>
                            <td style="color: var(--text-muted);">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td>
                                @if($user->is_active)
                                    <div style="display: flex; align-items: center; gap: 8px; color: #34d399; font-weight: 500; font-size: 0.9rem;">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: #34d399; box-shadow: 0 0 10px #34d399;"></div>
                                        Active
                                    </div>
                                @else
                                    <div style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-weight: 500; font-size: 0.9rem;">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--text-muted);"></div>
                                        Inactive
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="pagination-container">
        {{ $users->links('pagination::simple-default') }}
    </div>
@endsection
