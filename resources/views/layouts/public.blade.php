@extends('layouts.app')
@section('body')
<nav class="nav"><a class="brand" href="{{ route('home') }}">School Manager</a><div class="navlinks"><a href="{{ route('about') }}">About</a><a href="{{ route('academics') }}">Academics</a><a href="{{ route('admissions') }}">Admissions</a><a href="{{ route('contact') }}">Contact</a><a class="btn" href="{{ route('login') }}">Admin Login</a></div></nav>
@yield('content')
<footer class="footer"><div class="container"><p>School Manager &mdash; School Website & Management Platform</p></div></footer>
@endsection
