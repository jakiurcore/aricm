@extends('core::layouts.public')

@section('title', site_content()->text('contact.title'))

@section('content')
    @php($identity = app(\Modules\Core\Services\PublicSite::class)->identity())
    @include('website::sections.contact', ['map' => $map, 'phones' => $identity['phones'], 'address' => $identity['address'], 'email' => $identity['email']])
@endsection
