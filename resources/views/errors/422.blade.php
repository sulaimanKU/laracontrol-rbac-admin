@extends('layouts.app')
@section('title','422')
@section('content')<section class="card member-card"><span class="eyebrow">422 / REQUEST UNAVAILABLE</span><h1>This change cannot be applied.</h1><p>Check your selected role and status. Keep your own administrator access and at least one active administrator.</p><a class="button" href="{{ route('home') }}">Return home &rarr;</a></section>@endsection