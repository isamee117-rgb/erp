@extends('layouts.app')
@section('page-title', 'Dashboard - LeanERP')
@section('content')
<div id="dashboard-container"></div>
@endsection

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
:root{--db-primary:#CD0000;--db-font:'Inter',sans-serif;}
.page-body,.page-wrapper{font-family:var(--db-font);background:#F5F6FA!important;}

/* Page header */
.db-page-header{background:linear-gradient(135deg,#CD0000 0%,#e53333 100%);border-radius:10px;padding:22px 28px;margin-bottom:16px;position:relative;overflow:hidden;}
.db-page-header::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,0.12) 1px,transparent 1px);background-size:16px 16px;opacity:0.5;pointer-events:none;}
.db-page-header::after{content:'';position:absolute;top:-40%;right:-5%;width:240px;height:240px;background:rgba(255,255,255,0.06);border-radius:50%;pointer-events:none;}
.db-page-title{font-size:1.35rem;font-weight:700;color:#fff;margin:0 0 3px;position:relative;z-index:1;}
.db-page-subtitle{font-size:0.82rem;color:rgba(255,255,255,0.78);margin:0;position:relative;z-index:1;}
.db-master-badge{background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.3);border-radius:20px;padding:3px 12px;font-size:0.72rem;font-weight:700;letter-spacing:0.08em;position:relative;z-index:1;}

/* KPI Cards */
.db-kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px;}
@media(max-width:992px){.db-kpi-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:576px){.db-kpi-grid{grid-template-columns:1fr;}}
.db-kpi-card{background:#fff;border:1px solid #E8EAF0;border-radius:10px;padding:18px 20px;box-shadow:0 1px 3px rgba(0,0,0,0.05);display:flex;flex-direction:column;gap:10px;transition:transform 0.2s,box-shadow 0.2s;}
.db-kpi-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(205,0,0,0.1);}
.db-kpi-icon-wrap{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;}
.db-kpi-icon-green{background:rgba(16,185,129,0.12);color:#059669;}
.db-kpi-icon-blue{background:rgba(205,0,0,0.12);color:#CD0000;}
.db-kpi-icon-orange{background:rgba(249,115,22,0.12);color:#ea580c;}
.db-kpi-icon-purple{background:rgba(124,58,237,0.12);color:#7c3aed;}
.db-kpi-icon-yellow{background:rgba(234,179,8,0.12);color:#ca8a04;}
.db-kpi-icon-teal{background:rgba(20,184,166,0.12);color:#0d9488;}
.db-kpi-label{font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;margin:0;}
.db-kpi-value{font-size:1.5rem;font-weight:800;color:#1e293b;margin:0;letter-spacing:-0.5px;line-height:1.1;}
.db-kpi-sub{font-size:0.75rem;color:#94a3b8;margin:0;}

/* Section cards */
.db-section-card{background:#fff;border:1px solid #E8EAF0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden;margin-bottom:16px;}
.db-section-header{padding:14px 20px;border-bottom:1px solid #F0F2F8;display:flex;align-items:center;gap:8px;}
.db-section-title{font-size:0.88rem;font-weight:700;color:#1e293b;margin:0;}
.db-section-body{padding:16px 20px;}
.db-chart-body{padding:16px;}

/* Table */
.db-table{width:100%;border-collapse:collapse;}
.db-th{font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:10px 16px;background:#F8F9FC;border-bottom:2px solid #E8EAF0;white-space:nowrap;text-align:left;}
.db-td{padding:10px 16px;border-bottom:1px solid #F0F2F8;font-size:0.85rem;color:#374151;vertical-align:middle;}
.db-table tr:last-child .db-td{border-bottom:none;}
.db-table tbody tr:hover .db-td{background:#F5F7FF;}

/* Badges */
.db-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:600;}
.db-badge-green{background:rgba(16,185,129,0.1);color:#059669;}
.db-badge-red{background:rgba(239,68,68,0.1);color:#dc2626;}
.db-badge-blue{background:rgba(205,0,0,0.1);color:#CD0000;}

/* Activity list */
.db-activity-item{display:flex;justify-content:space-between;align-items:center;padding:10px 16px;border-bottom:1px solid #F0F2F8;}
.db-activity-item:last-child{border-bottom:none;}
.db-activity-id{font-weight:700;font-size:0.85rem;color:#1e293b;}
.db-activity-time{font-size:0.75rem;color:#94a3b8;margin-top:2px;}
.db-activity-amount{font-weight:700;font-size:0.88rem;color:#CD0000;}
.db-empty{text-align:center;padding:32px 16px;color:#94a3b8;font-size:0.85rem;}
.db-empty-icon{font-size:2rem;display:block;margin-bottom:8px;opacity:0.5;}

/* Rank badge */
.db-rank{font-size:0.8rem;font-weight:700;color:#94a3b8;min-width:24px;}
.db-company-avatar{width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#CD0000,#e53333);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;flex-shrink:0;}

/* Two-col row */
.db-row{display:grid;gap:14px;margin-bottom:16px;}
.db-row-8-4{grid-template-columns:2fr 1fr;}
@media(max-width:992px){.db-row-8-4{grid-template-columns:1fr;}}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="{{ asset('js/pages/dashboard.js') }}?v={{ filemtime(public_path('js/pages/dashboard.js')) }}"></script>
@endpush
