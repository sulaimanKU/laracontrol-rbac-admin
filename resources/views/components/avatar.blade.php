@props(['name', 'size' => '', 'tone' => 'teal'])
@php($initials = collect(preg_split('/\s+/u', trim($name)))->filter()->take(2)->map(fn($part) => mb_strtoupper(mb_substr($part,0,1)))->implode(''))
<span {{ $attributes->class(['avatar', 'avatar-'.$size => $size, 'avatar-'.$tone]) }} aria-hidden="true">{{ $initials }}</span>