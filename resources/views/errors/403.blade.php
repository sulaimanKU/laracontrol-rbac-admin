@extends('layouts.app')
@section('title', '403')
@section('content')<section class="card member-card"><span class="eyebrow">403 / REQUEST UNAVAILABLE</span>
        <h1>This area requires permission.</h1>
        <p>Your account does not have access to this action. Contact your administrator if you need help.</p><a
            class="button" href="{{ route('home') }}">Return home &rarr;</a>
</section>@endsection
