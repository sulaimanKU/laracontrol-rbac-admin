@props(['title', 'description' => '', 'icon' => 'users'])
<div class="empty"><span class="empty-icon"><x-icon :name="$icon"/></span><h3>{{ $title }}</h3><p>{{ $description }}</p>{{ $slot }}</div>