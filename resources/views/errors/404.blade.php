@extends('layouts.app')
@section('title','404')
@section('content')<section class="card member-card"><span class="eyebrow">404 / REQUEST UNAVAILABLE</span><h1>This page could not be found.</h1><p>Check the address or return to your workspace.</p><a class="button" href="{{ route('home') }}">Return home &rarr;</a></section>@endsection