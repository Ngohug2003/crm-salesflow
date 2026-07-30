@extends('layouts.app')

@section('content')
    <livewire:leads.lead-editor :lead-id="$lead->id" />
@endsection
