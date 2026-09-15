<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Indogate') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            .glass {
                background: rgba(15, 23, 42, 0.7);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            .text-gold {
                color: #D4AF37;
            }
            .bg-gold {
                background-color: #D4AF37;
            }
            .hover-gold:hover {
                color: #F3E5AB;
            }
            .btn-gold {
                background: linear-gradient(135deg, #D4AF37 0%, #AA8011 100%);
                color: #111827;
                transition: all 0.3s ease;
            }
            .btn-gold:hover {
                background: linear-gradient(135deg, #F3E5AB 0%, #D4AF37 100%);
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(212, 175, 55, 0.3);
            }
            .card-premium {
                background: rgba(30, 41, 59, 0.7);
                border: 1px solid rgba(255, 255, 255, 0.05);
                backdrop-filter: blur(8px);
                transition: all 0.3s ease;
            }
            .card-premium:hover {
                border-color: rgba(212, 175, 55, 0.3);
                transform: translateY(-4px);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-slate-900 text-slate-300 selection:bg-gold selection:text-slate-900">
        
        <!-- Navigation -->
        <nav class="fixed w-full z-50 glass transition-all duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center">
                        <a href="{{ route('search.index') }}" class="flex items-center gap-2">
                            <span class="text-2xl font-bold tracking-wider text-white">INDO<span class="text-gold">GATE</span></span>
                        </a>
                        
                        <div class="hidden md:flex items-center space-x-8 ml-10">
                            <a href="{{ route('search.index') }}" class="text-sm font-medium transition-colors hover-gold {{ request()->routeIs('search.*') ? 'text-gold' : 'text-slate-300' }}">Discover</a>
                            <a href="{{ route('dashboard') }}" class="text-sm font-medium transition-colors hover-gold {{ request()->routeIs('dashboard') || request()->routeIs('customer.bookings.*') ? 'text-gold' : 'text-slate-300' }}">My Bookings</a>
                        </div>
                    </div>

                    <div class="flex items-center gap-6">
                        <!-- Cart Icon -->
                        <a href="{{ route('cart.index') }}" class="relative p-2 text-slate-300 hover-gold transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            @if(session()->has('cart') && count(session('cart')) > 0)
                                <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-slate-900 bg-gold rounded-full transform translate-x-1/4 -translate-y-1/4">
                                    {{ count(session('cart')) }}
                                </span>
                            @endif
                        </a>

                        <!-- Profile Dropdown -->
                        <div class="relative x-data" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2 text-sm font-medium text-slate-300 hover-gold transition-colors focus:outline-none">
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <!-- Dropdown menu -->
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-slate-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-50 border border-slate-700"
                                 style="display: none;">
                                
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-300 hover:bg-slate-700 hover:text-white">Profile</a>
                                
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-slate-700 hover:text-red-300">
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="pt-20 min-h-screen flex flex-col">
            <!-- Header if exists -->
            @isset($header)
                <div class="relative bg-slate-800 border-b border-slate-700 py-8">
                    <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23D4AF37\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </div>
            @endisset

            <div class="flex-grow">
                {{ $slot }}
            </div>
            
            <!-- Minimal Footer -->
            <footer class="bg-slate-950 border-t border-slate-800 py-8 mt-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <p class="text-slate-500 text-sm">
                        &copy; {{ date('Y') }} Indogate. All rights reserved.
                    </p>
                </div>
            </footer>
        </main>
        
        <!-- Flash Messages -->
        @if (session()->has('success') || session()->has('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="fixed bottom-4 right-4 z-50">
                @if (session()->has('success'))
                    <div class="bg-emerald-900 border border-emerald-500 text-emerald-100 px-6 py-4 rounded-lg shadow-2xl flex items-center gap-3">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                
                @if (session()->has('error'))
                    <div class="bg-rose-900 border border-rose-500 text-rose-100 px-6 py-4 rounded-lg shadow-2xl flex items-center gap-3 mt-4">
                        <svg class="w-6 h-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
            </div>
        @endif
        
    </body>
</html>
