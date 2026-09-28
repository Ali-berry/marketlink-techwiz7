{{-- public pages ka fixed blob background (app.css ka .bg-blobs), layouts.public se ek baar include --}}
@props(['subtle' => false])

{{-- subtle: panels mein cream wash ke saath, warna tables aur forms se dhyan hat jata --}}
<div @class(['bg-blobs', 'bg-blobs--subtle' => $subtle]) aria-hidden="true">
    <div class="bg-blobs__blob bg-blobs__blob--green"></div>
    <div class="bg-blobs__blob bg-blobs__blob--orange"></div>
    <div class="bg-blobs__blob bg-blobs__blob--cream"></div>
    <div class="bg-blobs__blob bg-blobs__blob--green-soft"></div>
</div>
