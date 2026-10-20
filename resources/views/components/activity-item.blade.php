@props(['event'])
@php
$title = match($event->action) {
 'user.registered' => 'New account registered',
 'user.created' => 'User account created',
 'user.updated' => 'User account updated',
 'role.permissions_updated' => 'Permissions modified',
 'profile.updated' => 'Profile updated',
 'admin.bootstrapped' => 'Administrator created',
 default => ucfirst(str_replace(['.','_'],' ',$event->action)),
};
$icon = str_starts_with($event->action,'role.') ? 'shield' : 'user';
$description = $event->actor?->name ?? 'System / registration';
$description .= $event->subject ? ' updated '.$event->subject->name : ' updated workspace permissions';
if($event->action === 'user.registered') $description = ($event->subject?->name ?? 'A user').' joined the workspace';
if(in_array($event->action,['user.created','admin.bootstrapped'])) $description = ($event->actor?->name ?? 'System').' created '.($event->subject?->name ?? 'an account');
@endphp
<div class="activity-item"><span class="activity-icon"><x-icon :name="$icon"/></span><div class="activity-copy"><strong>{{ $title }}</strong><p>{{ $description }}</p><time datetime="{{ $event->created_at->toIso8601String() }}" title="{{ $event->created_at->format('M j, Y H:i:s') }}">{{ $event->created_at->diffForHumans() }}</time></div></div>