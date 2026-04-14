@extends('layouts.app')
@section('page-title', 'Job Card - LeanERP')
@section('content')
<div class="jc-page-wrap" id="jc-page-wrap">

    {{-- Print Header (hidden on screen, shown on print) --}}
    <div class="jc-print-header" id="jc-print-header"></div>

    {{-- Tab Bar --}}
    <div class="jc-tab-bar" id="jc-tab-bar">
        <button class="jc-new-tab-btn" id="jc-new-tab-btn">
            <i class="ti ti-plus"></i> New Job Card
        </button>
    </div>

    {{-- Active Card Panel --}}
    <div id="jc-panel-wrap"></div>

</div>
<script src="{{ asset('js/pages/job-card.js') }}"></script>
@endsection
