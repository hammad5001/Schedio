@extends('layouts.app')

@section('title', 'Super Admin — Registration Requests')

@section('content')
<div class="min-h-full flex flex-col">
    <!-- Top Navigation Header -->
    <header class="bg-slate-900/80 backdrop-blur-md border-b border-slate-800/80 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                    <div class="p-2 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-xl shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <span class="font-bold text-lg tracking-tight bg-gradient-to-r from-white to-slate-300 bg-clip-text text-transparent">
                        Schedio <span class="text-xs px-2 py-0.5 ml-1.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-mono font-medium">SUPER ADMIN</span>
                    </span>
                </a>

                <nav class="hidden md:flex items-center gap-1">
                    <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-lg text-sm text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 transition-all">
                        Dashboard
                    </a>
                    <a href="{{ route('admin.registration-requests.index') }}" class="px-3 py-1.5 rounded-lg text-sm font-semibold text-white bg-slate-800/90 border border-slate-700/60 shadow-sm">
                        Registration Requests
                        @if ($counts['pending'] > 0)
                            <span class="ml-1.5 px-1.5 py-0.5 text-xs font-bold rounded-full bg-amber-500 text-slate-950">
                                {{ $counts['pending'] }}
                            </span>
                        @endif
                    </a>
                </nav>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <div class="text-xs font-semibold text-slate-200">{{ auth()->user()->name }}</div>
                    <div class="text-[11px] text-indigo-400 font-mono">Super Admin</div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700/80 text-slate-400 hover:text-slate-200 transition-all text-xs font-medium cursor-pointer" title="Sign Out">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-500/30 text-emerald-300 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Status Filter Tabs -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Registration Requests</h1>
                <p class="text-sm text-slate-400">Review, assign department roles, approve or reject incoming user registrations.</p>
            </div>

            <div class="flex items-center gap-2 p-1 bg-slate-900 rounded-xl border border-slate-800">
                <a href="{{ route('admin.registration-requests.index', ['status' => 'pending']) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 {{ $status === 'pending' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200' }}">
                    <span>Pending</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $status === 'pending' ? 'bg-indigo-950 text-indigo-200' : 'bg-slate-800 text-slate-400' }}">{{ $counts['pending'] }}</span>
                </a>
                <a href="{{ route('admin.registration-requests.index', ['status' => 'approved']) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 {{ $status === 'approved' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200' }}">
                    <span>Approved</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $status === 'approved' ? 'bg-indigo-950 text-indigo-200' : 'bg-slate-800 text-slate-400' }}">{{ $counts['approved'] }}</span>
                </a>
                <a href="{{ route('admin.registration-requests.index', ['status' => 'rejected']) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 {{ $status === 'rejected' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200' }}">
                    <span>Rejected</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $status === 'rejected' ? 'bg-indigo-950 text-indigo-200' : 'bg-slate-800 text-slate-400' }}">{{ $counts['rejected'] }}</span>
                </a>
            </div>
        </div>

        <!-- Requests Listing -->
        <div class="bg-slate-900/70 border border-slate-800/80 rounded-2xl shadow-xl overflow-hidden">
            @if ($requests->isEmpty())
                <div class="text-center py-16 px-4">
                    <div class="inline-flex p-4 rounded-2xl bg-slate-800/50 text-slate-500 mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-slate-300">No {{ ucfirst($status) }} Registration Requests</h3>
                    <p class="text-xs text-slate-500 mt-1">There are no registration requests currently in this state.</p>
                </div>
            @else
                <div class="divide-y divide-slate-800/80">
                    @foreach ($requests as $req)
                        <div class="p-6 hover:bg-slate-850/40 transition-colors flex flex-col lg:flex-row lg:items-center justify-between gap-6" x-data="{ showRejectModal: false }">
                            <!-- Left: User Details -->
                            <div class="space-y-2 max-w-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white shadow-md">
                                        {{ strtoupper(substr($req->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                                            {{ $req->name }}
                                            @if ($req->approval_status === 'pending')
                                                <span class="text-[11px] px-2 py-0.5 rounded-full font-medium bg-amber-500/20 text-amber-300 border border-amber-500/30">Pending Review</span>
                                            @elseif ($req->approval_status === 'approved')
                                                <span class="text-[11px] px-2 py-0.5 rounded-full font-medium bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Approved ({{ ucfirst($req->role) }})</span>
                                            @elseif ($req->approval_status === 'rejected')
                                                <span class="text-[11px] px-2 py-0.5 rounded-full font-medium bg-rose-500/20 text-rose-300 border border-rose-500/30">Rejected</span>
                                            @endif
                                        </h3>
                                        <div class="text-xs text-slate-400 font-mono">
                                            {{ $req->email }}
                                            @if ($req->phone)
                                                <span class="text-slate-600 mx-1">&bull;</span> {{ $req->phone }}
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 text-xs text-slate-400 pt-1">
                                    <div>
                                        <span class="text-slate-500">Requested Title:</span>
                                        <span class="text-slate-300 font-medium ml-1">{{ $req->title ?? 'Not specified' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-500">Department:</span>
                                        <span class="text-slate-300 font-medium ml-1">{{ $req->department ? $req->department->name : 'Unassigned' }}</span>
                                    </div>
                                </div>

                                @if ($req->registration_notes)
                                    <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs text-slate-300">
                                        <span class="text-slate-500 font-semibold">User Note:</span> "{{ $req->registration_notes }}"
                                    </div>
                                @endif

                                @if ($req->rejection_reason)
                                    <div class="p-2.5 rounded-xl bg-rose-950/40 border border-rose-900/50 text-xs text-rose-300">
                                        <span class="text-rose-400 font-semibold">Rejection Reason:</span> {{ $req->rejection_reason }}
                                    </div>
                                @endif

                                <div class="text-[11px] text-slate-500">
                                    Submitted: {{ $req->created_at->format('M d, Y • h:i A') }} ({{ $req->created_at->diffForHumans() }})
                                </div>
                            </div>

                            <!-- Right: Approval Form or Actions -->
                            @if ($req->approval_status === 'pending')
                                <div class="bg-slate-950/60 p-4 rounded-xl border border-slate-800/80 min-w-[320px] space-y-3">
                                    <form action="{{ route('admin.registration-requests.approve', $req) }}" method="POST" class="space-y-3">
                                        @csrf
                                        @method('PATCH')

                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Assign Role</label>
                                                <select name="role" required class="w-full text-xs px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-slate-200">
                                                    <option value="employee" {{ $req->role === 'employee' ? 'selected' : '' }}>Employee</option>
                                                    <option value="manager" {{ $req->role === 'manager' ? 'selected' : '' }}>Manager</option>
                                                    <option value="client" {{ $req->role === 'client' ? 'selected' : '' }}>Client / External</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Department</label>
                                                <select name="department_id" class="w-full text-xs px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-slate-200">
                                                    <option value="">-- None / General --</option>
                                                    @foreach ($departments as $dept)
                                                        <option value="{{ $dept->id }}" {{ $req->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 pt-1">
                                            <button type="submit" class="flex-1 py-2 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-md shadow-emerald-600/20 transition-all cursor-pointer">
                                                Approve & Grant Access
                                            </button>
                                            <button type="button" @click="showRejectModal = !showRejectModal" class="py-2 px-3 rounded-lg bg-rose-950/60 hover:bg-rose-900/60 border border-rose-700/50 text-rose-300 text-xs font-semibold transition-all cursor-pointer">
                                                Reject
                                            </button>
                                        </div>
                                    </form>

                                    <!-- Inline Reject Prompt -->
                                    <div x-show="showRejectModal" x-cloak class="pt-3 border-t border-slate-800">
                                        <form action="{{ route('admin.registration-requests.reject', $req) }}" method="POST" class="space-y-2">
                                            @csrf
                                            @method('PATCH')
                                            <label class="block text-[10px] font-bold uppercase text-rose-400">Rejection Reason</label>
                                            <input type="text" name="rejection_reason" placeholder="e.g. Identity could not be verified" class="w-full text-xs px-2.5 py-1.5 bg-slate-900 border border-rose-800/60 rounded-lg text-rose-200 placeholder-rose-500/50">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="showRejectModal = false" class="text-[11px] text-slate-400 hover:text-white px-2 py-1">Cancel</button>
                                                <button type="submit" class="text-[11px] bg-rose-600 hover:bg-rose-500 text-white font-semibold px-2.5 py-1 rounded-lg">Confirm Rejection</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <div class="text-right text-xs text-slate-500 space-y-1">
                                    <div>Processed by: <span class="text-slate-300">{{ $req->approver ? $req->approver->name : 'Super Admin' }}</span></div>
                                    @if ($req->approved_at)
                                        <div>Date: <span class="text-slate-400">{{ $req->approved_at->format('M d, Y') }}</span></div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($requests->hasPages())
                    <div class="p-4 border-t border-slate-800">
                        {{ $requests->links() }}
                    </div>
                @endif
            @endif
        </div>
    </main>
</div>
@endsection
