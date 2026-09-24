@extends('layouts.app')

@section('title', 'Request Access')

@section('content')
<div class="min-h-full flex flex-col justify-center py-10 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-lg text-center">
        <!-- Logo Badge -->
        <div class="inline-flex items-center justify-center p-3 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-2xl shadow-xl shadow-indigo-500/20 ring-1 ring-white/20 mb-3">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-extrabold tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">
            Request Workspace Access
        </h1>
        <p class="mt-1 text-sm text-slate-400">
            Submit your profile details for Super Admin review and authorization.
        </p>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-lg">
        <div class="bg-slate-900/80 backdrop-blur-xl py-7 px-6 shadow-2xl shadow-black/50 rounded-2xl border border-slate-800/80 sm:px-10">

            @if ($errors->any())
                <div class="mb-5 p-4 rounded-xl bg-rose-950/60 border border-rose-500/30 text-rose-300 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <span>{{ $error }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <form class="space-y-4" action="{{ route('register.post') }}" method="POST">
                @csrf

                <!-- Name -->
                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                        Full Name <span class="text-rose-400">*</span>
                    </label>
                    <input id="name" name="name" type="text" required value="{{ old('name') }}"
                        placeholder="e.g. Elena Rostova"
                        class="mt-1 w-full px-4 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                        Work / Company Email <span class="text-rose-400">*</span>
                    </label>
                    <input id="email" name="email" type="email" required value="{{ old('email') }}"
                        placeholder="elena@company.com"
                        class="mt-1 w-full px-4 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                </div>

                <!-- Department & Title Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="department_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                            Department
                        </label>
                        <select id="department_id" name="department_id"
                            class="mt-1 w-full px-4 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                            <option value="">-- Select Team --</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="title" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                            Designation / Title
                        </label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}"
                            placeholder="e.g. Lead Designer"
                            class="mt-1 w-full px-4 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                    </div>
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                        Phone Number
                    </label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                        placeholder="+1 (555) 000-0000"
                        class="mt-1 w-full px-4 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                </div>

                <!-- Password & Confirm Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                            Password <span class="text-rose-400">*</span>
                        </label>
                        <input id="password" name="password" type="password" required
                            placeholder="Min 8 characters"
                            class="mt-1 w-full px-4 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                            Confirm Password <span class="text-rose-400">*</span>
                        </label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            placeholder="Re-type password"
                            class="mt-1 w-full px-4 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                    </div>
                </div>

                <!-- Registration Notes / Purpose -->
                <div>
                    <label for="registration_notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                        Reason for Access / Purpose (Optional)
                    </label>
                    <textarea id="registration_notes" name="registration_notes" rows="2"
                        placeholder="Brief note to Super Admin on your project or client assignment..."
                        class="mt-1 w-full px-4 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm resize-none">{{ old('registration_notes') }}</textarea>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 hover:shadow-indigo-600/40 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all cursor-pointer">
                        Submit Registration for Super Admin Approval
                    </button>
                </div>
            </form>

            <div class="mt-5 pt-5 border-t border-slate-800 text-center">
                <p class="text-xs text-slate-400">
                    Already registered?
                    <a href="{{ route('login') }}" class="font-semibold text-indigo-400 hover:text-indigo-300 transition-colors ml-1">
                        Sign In &rarr;
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
