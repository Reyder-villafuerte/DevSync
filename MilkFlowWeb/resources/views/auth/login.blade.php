@extends('layouts.app')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-10">
    <div class="w-full max-w-md bg-white p-8 sm:p-10 rounded-3xl border border-slate-200/80 shadow-lg relative overflow-hidden">
        
        <!-- Insignia Spark decorativa -->
        <div class="absolute -right-8 -top-8 w-24 h-24 bg-spark-cardDark rounded-full flex items-end justify-start p-4 text-spark-lime opacity-30 select-none">
            <i class="fa-solid fa-asterisk text-3xl"></i>
        </div>

        <div class="text-center mb-8">
            <div class="inline-flex p-3.5 bg-spark-dark text-spark-lime rounded-2xl mb-3 shadow-sm">
                <i class="fa-solid fa-asterisk text-2xl font-black"></i>
            </div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">MilkFlow Huata</h2>
            <p class="text-xs text-slate-500 mt-1">Plataforma de Acopio Distrital, Planta y Quesería</p>
        </div>

        <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[11px] uppercase font-bold text-slate-600 mb-1.5">Correo Electrónico</label>
                <div class="relative">
                    <i class="fa-regular fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="email" name="email" value="{{ old('email', 'admin@milkflow.com') }}" required 
                        placeholder="ejemplo@milkflow.com"
                        class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-spark-lime focus:border-transparent text-xs font-semibold text-slate-800 transition">
                </div>
            </div>

            <div>
                <label class="block text-[11px] uppercase font-bold text-slate-600 mb-1.5">Contraseña</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="password" name="password" value="password" required 
                        class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-spark-lime focus:border-transparent text-xs font-semibold text-slate-800 transition">
                </div>
            </div>

            <button type="submit" class="w-full bg-spark-dark hover:bg-black text-spark-lime font-black py-3 rounded-xl transition duration-200 shadow-md text-xs tracking-wider uppercase mt-2">
                Entrar al Sistema
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 text-xs text-slate-500">
            <span class="font-bold text-slate-700 block mb-2 text-[11px] uppercase tracking-wider">Acceso Rápido por Rol (Clave: <code class="bg-slate-100 px-1 py-0.5 rounded text-slate-800 font-bold">password</code>):</span>
            <div class="grid grid-cols-2 gap-1.5 text-[10px] text-slate-600">
                <div>• Admin: <code class="font-mono text-slate-800">admin@milkflow.com</code></div>
                <div>• Acopiador: <code class="font-mono text-slate-800">acopiador1@milkflow.com</code></div>
                <div>• Jefe Planta: <code class="font-mono text-slate-800">produccion@milkflow.com</code></div>
                <div>• Ventas: <code class="font-mono text-slate-800">ventas@milkflow.com</code></div>
                <div>• Calidad: <code class="font-mono text-slate-800">calidad@milkflow.com</code></div>
                <div>• Productor: <code class="font-mono text-slate-800">juan.perez@huata.pe</code></div>
            </div>
        </div>
    </div>
</div>
@endsection
