@extends('layouts.admin-sidebar')

@section('title', 'Calendar · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')
    <style>
        /* ---- Role strip ---- */
        .role-strip {
            display: flex; align-items: center; gap: 10px 14px; flex-wrap: wrap;
            padding: 8px 28px; background: #FFFDFA; border-bottom: 1px solid #EBE4DA;
            font: 700 12px 'Nunito Sans'; color: #5A6B7E;
        }
        .role-badge { background: #16436E; color: #fff; border-radius: 7px; padding: 3px 10px; font: 800 11.5px 'Nunito Sans'; }
        .role-can { color: #2E7D5B; }
        .role-locked { color: #98897A; }

        /* ---- Top bar ---- */
        .cal-topbar {
            display: flex; align-items: center; gap: 12px 14px; flex-wrap: wrap;
            padding: 16px 28px 12px; background: #FFFDFA;
        }
        .cal-topbar-title { flex: 1 1 260px; min-width: 220px; }
        .cal-daylabel { font: 600 21px/1.2 'Baloo 2'; color: #16436E; }
        .cal-subtitle { font: 600 12.5px 'Nunito Sans'; color: #98897A; margin-top: 2px; }

        .cal-view-toggle { display: flex; gap: 6px; }
        .cal-view-btn {
            border: 1px solid #E2DACE; background: #fff; padding: 9px 16px;
            border-radius: 9px; font: 800 12.5px 'Nunito Sans'; color: #16436E; cursor: pointer;
        }
        .cal-view-btn.active { background: #C8355F; border-color: #C8355F; color: #fff; }

        .cal-nav { display: flex; align-items: center; gap: 6px; }
        .cal-nav-arrow {
            background: #fff; border: 1px solid #E2DACE; border-radius: 8px;
            width: 30px; height: 34px; font: 700 15px 'Nunito Sans'; color: #16436E; cursor: pointer;
        }
        .cal-nav-arrow:hover { border-color: #C8355F; color: #C8355F; }
        .cal-nav-label { font: 800 13px 'Nunito Sans'; color: #16436E; min-width: 150px; text-align: center; }
        .cal-select {
            padding: 9px 12px; border: 1px solid #E2DACE; border-radius: 9px; background: #fff;
            font: 800 12.5px 'Nunito Sans'; color: #16436E; outline: none; cursor: pointer;
        }

        .cal-btn {
            background: #fff; color: #16436E; border: 1px solid #E2DACE; border-radius: 10px;
            padding: 10px 16px; font: 800 12.5px 'Nunito Sans'; cursor: pointer; white-space: nowrap;
        }
        .cal-btn:hover { border-color: #C8355F; }
        .cal-btn-purple { background: #EDE7F5; color: #4B3F9E; border-color: #D9CFF0; }
        .cal-btn-purple.active { background: #6E4FA8; color: #fff; border-color: #6E4FA8; }
        .cal-btn-primary { background: #C8355F; color: #fff; border-color: #C8355F; }
        .cal-btn-primary:hover { background: #A82348; }

        /* ---- Legend ---- */
        .cal-legend { display: flex; gap: 8px; flex-wrap: wrap; padding: 0 28px 12px; background: #FFFDFA; border-bottom: 1px solid #EBE4DA; }
        .legend-chip { border-radius: 7px; padding: 4px 10px; font: 800 10.5px 'Nunito Sans'; letter-spacing: .04em; text-transform: uppercase; }

        /* ---- Day view ---- */
        .cal-grid-wrap { flex: 1; display: flex; min-height: 0; }
        .cal-grid { flex: 1; overflow: auto; padding: 20px 24px; display: flex; gap: 14px; align-items: flex-start; }
        /* Same slim scrollbar as the sidebar - no bulky OS-default one */
        .cal-grid, .roster-wrap, .month-grid { scrollbar-width: thin; scrollbar-color: #DDD4C8 transparent; }
        .cal-grid::-webkit-scrollbar, .roster-wrap::-webkit-scrollbar, .month-grid::-webkit-scrollbar { width: 4px; height: 4px; }
        .cal-grid::-webkit-scrollbar-track, .roster-wrap::-webkit-scrollbar-track, .month-grid::-webkit-scrollbar-track { background: transparent; }
        .cal-grid::-webkit-scrollbar-thumb, .roster-wrap::-webkit-scrollbar-thumb, .month-grid::-webkit-scrollbar-thumb { background: #DDD4C8; border-radius: 5px; }
        .cal-grid::-webkit-scrollbar-button, .roster-wrap::-webkit-scrollbar-button, .month-grid::-webkit-scrollbar-button { display: none; width: 0; height: 0; }
        .therapist-col { width: 226px; flex-shrink: 0; background: #F6F3EE; border-radius: 14px; padding: 8px; transition: box-shadow .15s ease; }
        .therapist-col.drag-over { box-shadow: inset 0 0 0 2px rgba(200,53,95,.35); }
        .therapist-col-header { background: #16436E; border-radius: 10px; padding: 10px 14px; margin-bottom: 10px; }
        .therapist-col-name { font: 600 14px 'Baloo 2'; color: #fff; }
        .therapist-col-sub { font: 600 11px 'Nunito Sans'; color: #9FB6CC; }
        .col-body { display: flex; flex-direction: column; gap: 8px; min-height: 40px; }
        .col-empty { text-align: center; color: #B0A493; font: 600 11.5px 'Nunito Sans'; padding: 16px 4px; }

        .session-card { border-radius: 11px; padding: 10px 13px; display: flex; flex-direction: column; gap: 5px; transition: transform .15s ease, box-shadow .15s ease; position: relative; }
        .session-card[draggable="true"] { cursor: grab; }
        .session-card:hover { transform: translateY(-2px); box-shadow: 0 8px 18px -10px rgba(22,42,60,.3); }
        .card-top { display: flex; justify-content: space-between; align-items: baseline; }
        .card-time { font: 600 13px 'Baloo 2'; }
        .card-duration { font: 600 10.5px 'Nunito Sans'; color: #98897A; }
        .card-name { font: 800 13px 'Nunito Sans'; color: #2B3A4C; }
        .card-meta { font: 600 11.5px 'Nunito Sans'; color: #8A7D6C; }
        .card-hint { font: 600 10px 'Nunito Sans'; color: #98897A; }
        .card-btn { width: 100%; background: #fff; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer; }
        .session-card.is-cancelled .card-name, .session-card.is-cancelled .card-time { text-decoration: line-through; }
        .leave-card { background: #FDF3B0; border-radius: 11px; padding: 10px 13px; }
        .leave-card-title { font: 800 12.5px 'Nunito Sans'; color: #7A5C00; }
        .leave-card-reason { font: 600 11px 'Nunito Sans'; color: #8A7D6C; margin-top: 2px; }

        .sup-badge { display: inline-block; background: #6E4FA8; color: #fff; font: 800 9.5px 'Nunito Sans'; letter-spacing: .04em; padding: 2px 7px; border-radius: 5px; }
        .sup-note { font: 600 10.5px 'Nunito Sans'; color: #5A6B7E; margin-top: 3px; white-space: normal; line-height: 1.35; }
        .sup-mode .sup-pick { outline: 2px solid #6E4FA8; outline-offset: 1px; cursor: pointer; }
        .sup-mode .sup-dim { cursor: default; }

        /* ---- Week view: staff roster grid ---- */
        .roster-wrap { flex: 1; overflow: auto; padding: 18px 24px; }
        .roster { width: 100%; min-width: 1040px; border-collapse: separate; border-spacing: 0; background: #fff; border: 1px solid #EBE4DA; border-radius: 12px; overflow: hidden; }
        .roster th { text-align: left; font: 800 10.5px 'Nunito Sans'; letter-spacing: .05em; text-transform: uppercase; color: #98897A; padding: 10px 12px; background: #F6F3EE; border-bottom: 1px solid #EBE4DA; border-right: 1px solid #F0EAE0; }
        .roster th.day { text-align: center; }
        .roster th.day small { display: block; font: 700 10px 'Nunito Sans'; color: #98897A; text-transform: none; letter-spacing: 0; margin-top: 1px; }
        .roster th.today { background: #FBE9EE; color: #C8355F; }
        .roster th.today small { color: #C8355F; }
        .roster td { vertical-align: top; padding: 8px; border-bottom: 1px solid #F0EAE0; border-right: 1px solid #F3EDE3; min-width: 124px; height: 96px; }
        .roster td.no { width: 42px; font: 700 12px 'Nunito Sans'; color: #98897A; vertical-align: middle; text-align: center; }
        .roster td.name { width: 170px; font: 800 13.5px 'Nunito Sans'; color: #16436E; vertical-align: middle; }
        .roster td.desig { width: 150px; font: 600 12px 'Nunito Sans'; color: #8A7D6C; vertical-align: middle; }
        .roster td.off { background: #F5E3C0; text-align: center; vertical-align: middle; font: 800 12px 'Nunito Sans'; color: #8A6A2B; }
        .roster td.today-col { background: #FFF8FA; }
        .blk { border-radius: 7px; padding: 5px 8px; margin-bottom: 5px; cursor: pointer; border: 1px solid transparent; position: relative; }
        .blk:hover { border-color: rgba(22,67,110,.25); }
        .blk-time { font: 800 11px 'Nunito Sans'; }
        .blk-name { font: 700 11px 'Nunito Sans'; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .blk.is-cancelled .blk-time, .blk.is-cancelled .blk-name { text-decoration: line-through; }
        .blk .sup-badge { margin-top: 4px; }
        .free-slot { display: inline-block; background: #FBE9EE; color: #C8355F; font: 800 10px 'Nunito Sans'; letter-spacing: .04em; padding: 3px 8px; border-radius: 6px; }
        .cell-add { display: inline-block; font: 800 13px 'Nunito Sans'; color: #C8355F; cursor: pointer; margin-top: 2px; padding: 0 4px; }
        .cell-add:hover { text-decoration: underline; }

        /* ---- Month view ---- */
        .month-grid { flex: 1; overflow: auto; padding: 20px 24px; display: grid; grid-template-columns: repeat(7, minmax(130px, 1fr)); gap: 10px; align-content: start; }
        .month-dow { font: 800 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: .5px; padding: 0 4px 4px; }
        .month-cell { background: #fff; border: 1px solid #EBE4DA; border-radius: 12px; padding: 8px; min-height: 112px; cursor: pointer; display: flex; flex-direction: column; gap: 3px; }
        .month-cell:hover { border-color: #C8355F; }
        .month-cell.is-today { border: 2px solid #C8355F; }
        .month-cell.is-empty { background: transparent; border-color: transparent; cursor: default; }
        .month-cell.is-weekend { background: #F6F3EE; }
        .month-cell-top { display: flex; justify-content: space-between; align-items: baseline; }
        .month-cell-date { font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; }
        .month-cell-count { font: 800 11px 'Nunito Sans'; color: #C8355F; }
        .month-chip { border-radius: 6px; padding: 2px 6px; font: 700 10.5px 'Nunito Sans'; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .month-chip.is-cancelled { text-decoration: line-through; }
        .month-more { font: 700 10px 'Nunito Sans'; color: #98897A; }
        .month-book-link { font: 800 10.5px 'Nunito Sans'; color: #C8355F; margin-top: auto; cursor: pointer; align-self: flex-start; }
        .month-book-link:hover { text-decoration: underline; }

        /* ---- Panel (book / edit) ---- */
        .panel-overlay { display: none; position: fixed; inset: 0; background: rgba(22,67,110,0.15); z-index: 50; }
        .panel { position: absolute; top: 0; right: 0; height: 100vh; width: 420px; max-width: 94vw; background: #FFFDFA; box-shadow: -24px 0 60px rgba(22,67,110,0.15); padding: 24px 24px 30px; overflow-y: auto; display: flex; flex-direction: column; gap: 14px; border-left: 6px solid #C8355F; }
        .panel-title { font: 700 20px 'Baloo 2'; color: #16436E; }
        .f-label { display: block; font: 800 11px 'Nunito Sans'; letter-spacing: .04em; color: #8A7D6C; text-transform: uppercase; margin-bottom: 6px; }
        .f-input, .f-select, .f-textarea { width: 100%; padding: 11px 10px; border: 1px solid #E2DACE; border-radius: 9px; background: #FFFDFA; font: 700 13.5px 'Nunito Sans'; color: #16436E; outline: none; box-sizing: border-box; }
        .f-textarea { font-weight: 600; resize: vertical; }
        .f-input:disabled, .f-select:disabled, .f-textarea:disabled { background: #F6F3EE; color: #8A7D6C; }
        .f-help { font: 600 11px 'Nunito Sans'; color: #98897A; margin-top: 5px; }
        .f-error { display: none; background: #F9E7EC; color: #C8355F; font: 700 12px 'Nunito Sans'; padding: 8px 10px; border-radius: 8px; }
        .f-row { display: flex; gap: 10px; }
        .f-row > div { flex: 1; }
        .f-time-trigger { display: flex; align-items: center; justify-content: space-between; text-align: left; cursor: pointer; }
        .f-time-trigger-caret { color: #98897A; font-size: 11px; transition: transform 0.15s ease; }
        .f-time-trigger.open .f-time-trigger-caret { transform: rotate(180deg); }
        .time-grid { display: none; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 8px; }
        .time-grid.open { display: grid; }
        .time-slot { background: #FFFDFA; border: 1px solid #E2DACE; border-radius: 9px; padding: 9px 4px; text-align: center; font: 700 12.5px 'Nunito Sans'; color: #16436E; cursor: pointer; }
        .time-slot:hover { border-color: #C8355F; }
        .time-slot.selected { background: #C8355F; border-color: #C8355F; color: #fff; }
        .time-slot.busy { background: #F6F3EE; border-color: #E2DACE; color: #B0A493; text-decoration: line-through; cursor: not-allowed; }
        .time-slot.busy:hover { border-color: #E2DACE; }
        .time-grid-empty { font: 600 12px 'Nunito Sans'; color: #98897A; grid-column: 1 / -1; }
        .f-time-trigger.is-disabled { pointer-events: none; opacity: .6; }
        .btn-save { background: #C8355F; border: none; border-radius: 10px; padding: 13px 0; font: 800 13.5px 'Nunito Sans'; color: #fff; cursor: pointer; width: 100%; }
        .btn-cancel { background: #fff; border: 1px solid #E2DACE; border-radius: 10px; padding: 13px 0; font: 800 13.5px 'Nunito Sans'; color: #5A6B7E; cursor: pointer; width: 100%; }
        .btn-danger { background: #fff; border: 1px solid #EFC7C2; border-radius: 10px; padding: 11px 0; font: 800 12.5px 'Nunito Sans'; color: #B3261E; cursor: pointer; width: 100%; }

        /* ---- Multi-select ---- */
        .ms { position: relative; }
        .ms-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
        .ms-chips:empty { display: none; }
        .ms-chip { background: #C8355F; color: #fff; border-radius: 8px; padding: 5px 10px; font: 800 11.5px 'Nunito Sans'; display: inline-flex; gap: 7px; align-items: center; }
        .ms-chip button { background: none; border: none; color: #fff; cursor: pointer; font: 800 12px 'Nunito Sans'; padding: 0; line-height: 1; }
        .ms-trigger { width: 100%; text-align: left; padding: 11px 10px; border: 1px solid #E2DACE; border-radius: 9px; background: #FFFDFA; font: 700 13.5px 'Nunito Sans'; color: #16436E; cursor: pointer; display: flex; justify-content: space-between; align-items: center; box-sizing: border-box; }
        .ms-trigger.open { border-color: #C8355F; }
        .ms-trigger .caret { font-size: 10px; color: #98897A; }
        .ms-trigger.placeholder { color: #98897A; font-weight: 600; }
        .ms-pop { position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 30; background: #fff; border: 1px solid #E2DACE; border-radius: 10px; box-shadow: 0 12px 30px rgba(22,67,110,.15); padding: 8px; max-height: 250px; overflow: auto; display: none; }
        .ms-pop.open { display: block; }
        .ms-search { width: 100%; padding: 8px 10px; border: 1px solid #E2DACE; border-radius: 8px; margin-bottom: 6px; font: 600 12.5px 'Nunito Sans'; box-sizing: border-box; outline: none; }
        .ms-opt { display: flex; gap: 9px; align-items: center; padding: 7px 8px; border-radius: 7px; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; cursor: pointer; }
        .ms-opt:hover { background: #F6F3EE; }
        .ms-opt.sel { background: #FBE9EE; }
        .ms-opt input { accent-color: #C8355F; }
        .ms-empty { font: 600 12px 'Nunito Sans'; color: #98897A; padding: 6px 8px; }
        .ms-custom { display: flex; gap: 8px; margin-top: 8px; }
        .ms-custom input { flex: 1; padding: 9px 10px; border: 1px dashed #D8CDBC; border-radius: 9px; font: 600 12.5px 'Nunito Sans'; color: #16436E; outline: none; box-sizing: border-box; }
        .ms-custom button { background: #fff; border: 1px solid #E2DACE; border-radius: 9px; padding: 0 14px; font: 800 12px 'Nunito Sans'; color: #16436E; cursor: pointer; }

        /* ---- Centered modals ---- */
        .cmodal-overlay { display: none; position: fixed; inset: 0; background: rgba(22,67,110,0.25); z-index: 60; align-items: center; justify-content: center; padding: 20px; }
        .cmodal-overlay.open { display: flex; }
        .cmodal { background: #FFFDFA; border-radius: 16px; padding: 24px; width: 100%; max-width: 420px; box-shadow: 0 24px 60px rgba(22,67,110,.25); display: flex; flex-direction: column; gap: 14px; max-height: 92vh; overflow: auto; }
        .cmodal.wide { max-width: 900px; }
        .cmodal-title { font: 700 20px 'Baloo 2'; color: #16436E; }
        .cmodal-sub { font: 600 12.5px 'Nunito Sans'; color: #98897A; margin-top: -8px; }
        .impact-box { background: #FDF6E9; border: 1px solid #EBDCC2; border-radius: 9px; padding: 10px 12px; font: 700 12px 'Nunito Sans'; color: #8A5A10; }
        .impact-box.ok { background: #E4F6EB; border-color: #BFE9CE; color: #1E8A4C; }
        .util-table { width: 100%; border-collapse: collapse; }
        .util-table th { text-align: left; font: 800 10.5px 'Nunito Sans'; text-transform: uppercase; letter-spacing: .05em; color: #98897A; padding: 10px 12px; border-bottom: 1px solid #EBE4DA; background: #F6F3EE; }
        .util-table th small { display: block; text-transform: none; letter-spacing: 0; font-weight: 700; }
        .util-table td { padding: 11px 12px; border-bottom: 1px solid #F3EDE3; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; }
        .util-table td.pos { color: #8A7D6C; font-weight: 600; }
        .util-table td.num, .util-table th.num { text-align: center; }
        .util-table tr.total td { background: #F6F3EE; font-weight: 800; }
        .util-table td.grand { color: #C8355F; }
        .toast { position: fixed; bottom: 22px; left: 50%; transform: translateX(-50%); background: #16436E; color: #fff; padding: 11px 18px; border-radius: 10px; font: 800 12.5px 'Nunito Sans'; z-index: 100; display: none; box-shadow: 0 10px 30px rgba(22,67,110,.3); }

        @media (max-width: 640px) {
            .cal-topbar { padding: 12px 14px; gap: 10px; }
            .cal-daylabel { font-size: 17px; }
            .cal-subtitle { display: none; }
            .cal-grid { padding: 12px 14px; gap: 10px; }
            .panel { width: 100vw !important; max-width: 100vw !important; padding: 18px 16px 24px !important; }
        }

        /* ---- Phone ----------------------------------------------------------
           Three views that all assume a wide screen: a row of therapist columns,
           a 1040px roster, and a seven-column month. Each one is reworked below
           rather than left to be dragged sideways. */
        @media (max-width: 820px) {
            /* The role strip is reference text, not something acted on, and it
               was taking a third of the screen above the calendar. The badge
               says which role you are in; the rest is on the roles page. */
            .role-strip { padding: 7px 14px; gap: 8px; }
            .role-can, .role-locked { display: none; }

            .cal-topbar { padding: 10px 14px 8px; gap: 8px; }
            .cal-topbar-title { flex: 1 1 100%; min-width: 0; }
            .cal-daylabel { font-size: 16px; }
            .cal-view-toggle { flex: 1 1 100%; }
            .cal-view-btn { flex: 1; padding: 8px 0; }
            /* The date nav gets its own line, then the actions pair off two to a
               row instead of breaking wherever they happen to run out of width.
               Booking is the primary action, so it takes a full row. */
            .cal-nav { flex: 1 1 100%; }
            .cal-nav-label { min-width: 0; flex: 1; font-size: 12px; }
            .cal-select { padding: 8px 12px; font-size: 11.5px; flex: 1; }
            .cal-btn {
                flex: 1 1 calc(50% - 4px); min-width: 0;
                padding: 10px 8px; font-size: 11.5px; text-align: center;
            }
            .cal-btn-primary { flex: 1 1 100%; }

            /* One row that scrolls, rather than three rows of chips before the
               calendar even starts. */
            .cal-legend {
                flex-wrap: nowrap; overflow-x: auto; padding: 0 14px 10px;
                scrollbar-width: none; -ms-overflow-style: none;
            }
            .cal-legend::-webkit-scrollbar { display: none; }
            .legend-chip { flex-shrink: 0; }

            /* Day: the same shape as the leads pipeline - a stacked list of
               therapists, all shut, one open at a time. Swiping between narrow
               columns meant never seeing more than one person at a time, and no
               sense of who else is on today. */
            .cal-grid {
                padding: 12px 14px; gap: 8px;
                flex-direction: column; align-items: stretch;
                overflow-x: hidden; overflow-y: auto;
            }
            .therapist-col {
                width: auto; max-width: none; padding: 0;
                background: #F6F3EE; border-radius: 12px; overflow: hidden;
            }
            .therapist-col-header {
                display: flex; align-items: center; gap: 10px;
                margin-bottom: 0; border-radius: 0; cursor: pointer;
                user-select: none; -webkit-tap-highlight-color: transparent;
            }
            .therapist-col-header > div { flex: 1; min-width: 0; }
            .therapist-col-header::after {
                content: ''; flex: none; width: 7px; height: 7px; margin-right: 3px;
                border-right: 2px solid #9FB6CC; border-bottom: 2px solid #9FB6CC;
                transform: rotate(-45deg);
                transition: transform .22s cubic-bezier(.4, 0, .2, 1);
            }
            .therapist-col.is-open .therapist-col-header::after { transform: rotate(45deg); }
            /* Height rather than display, so it can be animated. The open height
               is set on the element by the script, which is the only thing that
               knows how many sessions are inside. */
            .col-body {
                min-height: 0; max-height: 0; opacity: 0; overflow: hidden;
                padding: 0 8px;
                transition: max-height .3s cubic-bezier(.4, 0, .2, 1),
                            padding .3s cubic-bezier(.4, 0, .2, 1),
                            opacity .22s ease;
            }
            /* Same reason as the roles cards: a capped flex column shrinks its
               children rather than letting them overflow. */
            .col-body > * { flex-shrink: 0; }
            .therapist-col.is-open .col-body { padding: 8px; opacity: 1; overflow-y: auto; }
            /* Reassigning by drag is a mouse gesture; the hint is noise here. */
            .card-hint { display: none; }

            @media (prefers-reduced-motion: reduce) {
                .col-body, .therapist-col-header::after { transition: none; }
            }

            /* Week: the row number and the designation are reference detail
               costing a third of the width, so they go. The name column is the
               one pinned, so a row is never anonymous once the days scroll. */
            /* No side padding on the scroll container: a sticky cell parks at
               the padding edge, not the border edge, so a 14px gutter left a
               strip beside the pinned name column for the scrolling days to
               show through. */
            .roster-wrap { padding: 12px 0; }
            /* overflow:hidden on the table (there for the rounded corners)
               makes it a scroll container of its own, which pins the sticky
               cell to the table instead of to the viewport - so it has to go
               for the name column to hold its place. */
            .roster { min-width: 660px; overflow: visible; }
            .roster th:nth-child(1), .roster td:nth-child(1),
            .roster th:nth-child(3), .roster td:nth-child(3) { display: none; }
            .roster th:nth-child(2), .roster td:nth-child(2) {
                position: sticky; left: 0; z-index: 2;
                width: 98px; min-width: 98px; background: #fff;
                /* Reads as the days passing underneath rather than into it. */
                box-shadow: 3px 0 6px -2px rgba(22, 42, 60, .18);
            }
            .roster th:nth-child(2) { z-index: 3; background: #F6F3EE; }
            .roster td { min-width: 92px; height: auto; padding: 6px; }


            /* Month has to stay seven columns wide, so the cells shrink until
               the whole month fits the screen and read as an overview - the day
               number and how many sessions are on it. Tapping one opens the day. */
            .month-grid { padding: 12px 10px; gap: 4px; grid-template-columns: repeat(7, minmax(0, 1fr)); }
            .month-dow { font-size: 9px; padding: 0 0 3px; text-align: center; letter-spacing: 0; }
            .month-cell { min-height: 58px; padding: 5px 3px; border-radius: 8px; gap: 1px; }
            .month-cell.is-today { border-width: 1.5px; }
            .month-cell-top { justify-content: center; gap: 3px; }
            .month-cell-date { font-size: 11.5px; }
            .month-cell-count {
                background: #C8355F; color: #fff; border-radius: 999px;
                min-width: 15px; padding: 0 4px; text-align: center;
                font-size: 9.5px; line-height: 15px;
            }
            /* The patient's name stays on the chip - it is the one thing the
               month is read for - and truncates with an ellipsis rather than
               being dropped. The time goes instead: in a cell this narrow it
               would eat the whole line and leave nothing for the name. */
            .month-chip {
                display: block; padding: 1px 3px; border-radius: 4px;
                font: 700 8.5px 'Nunito Sans'; line-height: 1.5;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            .month-chip-time { display: none; }
            .month-more { display: block; font-size: 8px; }
            .month-book-link { display: none; }
        }
    </style>

    <div id="calendar-root"
         data-staff='@json($staffForJs)'
         data-leads='@json($leadsForJs)'
         data-types='@json($types)'
         data-leave-types='@json($leaveTypes)'
         data-durations='@json($durations)'
         data-can-manage="{{ $canManage ? '1' : '0' }}"
         data-can-book="{{ $canBook ? '1' : '0' }}"
         data-me="{{ $currentUserId }}"
         data-me-name="{{ $currentUserName }}"
         data-feed-url="{{ route('calendar.feed') }}"
         data-leads-url="{{ route('calendar.leads') }}"
         data-store-url="{{ route('calendar.store') }}"
         data-base-url="{{ url('calendar') }}"
         data-utilisation-url="{{ route('calendar.utilisation') }}"
         data-leave-url="{{ route('calendar.leave.store') }}"
         data-leave-impact-url="{{ route('calendar.leave.impact') }}"
         data-initial-session-id="{{ $initialSessionId }}"
         data-initial-date="{{ $initialDate }}"
         style="flex: 1; display: flex; flex-direction: column; min-height: 0; margin: -22px -28px 0 -28px;">

        <div class="role-strip">
            <span class="role-badge">{{ $capabilities['label'] }}</span>
            <span class="role-can">Can {{ implode(' · ', $capabilities['can']) }}</span>
            @if ($capabilities['locked'])
                <span class="role-locked">
                    🔒 Locked: {{ implode(' · ', array_slice($capabilities['locked'], 0, 4)) }}@if (count($capabilities['locked']) > 4) · +{{ count($capabilities['locked']) - 4 }} more @endif
                </span>
            @endif
        </div>

        <div class="cal-topbar">
            <div class="cal-topbar-title">
                <div class="cal-daylabel" id="cal-title">Calendar</div>
                <div class="cal-subtitle" id="cal-subtitle"></div>
            </div>

            <div class="cal-view-toggle">
                <button type="button" class="cal-view-btn active" data-view="day">Day</button>
                <button type="button" class="cal-view-btn" data-view="week">Week</button>
                <button type="button" class="cal-view-btn" data-view="month">Month</button>
            </div>

            <div class="cal-nav" id="nav-day">
                <button type="button" class="cal-nav-arrow" data-nav="-1" aria-label="Previous day">‹</button>
                <select id="day-pick" class="cal-select"></select>
                <button type="button" class="cal-nav-arrow" data-nav="1" aria-label="Next day">›</button>
            </div>
            <div class="cal-nav" id="nav-range" style="display:none;">
                <button type="button" class="cal-nav-arrow" data-nav="-1" aria-label="Previous">‹</button>
                <span class="cal-nav-label" id="nav-range-label"></span>
                <button type="button" class="cal-nav-arrow" data-nav="1" aria-label="Next">›</button>
            </div>

            <button type="button" class="cal-btn" id="btn-utilisation">Utilisation report</button>
            @if ($canManage)
                <button type="button" class="cal-btn" id="btn-leave">Mark leave</button>
                <button type="button" class="cal-btn cal-btn-purple" id="btn-supervision">Log supervision</button>
            @endif
            @if ($canBook)
                <button type="button" class="cal-btn cal-btn-primary" id="btn-book">+ Book session</button>
            @endif
        </div>

        <div class="cal-legend" id="cal-legend"></div>

        <div class="cal-grid-wrap" id="day-view">
            <div id="calendar-grid" class="cal-grid"></div>
        </div>

        <div class="cal-grid-wrap" id="week-view" style="display:none;">
            <div class="roster-wrap"><table class="roster" id="roster"></table></div>
        </div>

        <div class="cal-grid-wrap" id="month-view" style="display:none;">
            <div id="month-grid" class="month-grid"></div>
        </div>
    </div>

    <!-- Book / Edit slide-out panel -->
    <div id="session-panel" class="panel-overlay">
        <div class="panel" id="panel-inner">
            <div class="panel-title" id="panel-title">Book session</div>
            <div class="f-error" id="panel-error"></div>

            <form id="session-form" style="display:flex; flex-direction:column; gap:14px;">
                <input type="hidden" id="f-id" value="">

                <div>
                    <label class="f-label">Therapist / assign to</label>
                    <div id="ms-therapists"></div>
                </div>

                <div style="display:flex; gap:10px;">
                    <div id="f-repeats-wrap" style="flex:1;">
                        <label class="f-label" for="f-repeats">Repeats</label>
                        <select id="f-repeats" class="f-select">
                            <option value="weekly" selected>Every week</option>
                            <option value="none">One-off on a date</option>
                        </select>
                    </div>

                    <div style="flex:1;">
                        <label class="f-label" id="f-day-label">Date</label>
                        <input type="date" id="f-day" class="f-input" required>
                    </div>
                </div>

                <div id="f-weekdays-wrap">
                    <label class="f-label">Days of the week</label>
                    <div id="ms-weekdays"></div>
                </div>

                <div>
                    <label class="f-label">Patient</label>
                    <div id="ms-patients"></div>
                </div>

                <div id="pkg-info" style="display:none; background:#F3EDE3; border-radius:10px; padding:10px 12px;">
                    <div style="font:800 12.5px 'Nunito Sans'; color:#2B3A4C; margin-bottom:5px;" id="pkg-info-title"></div>
                    <div style="display:flex; gap:14px; flex-wrap:wrap; font:700 11.5px 'Nunito Sans'; color:#5A6B7E;">
                        <span>Allocated: <strong id="pkg-info-total"></strong></span>
                        <span>Used / Scheduled: <strong id="pkg-info-used"></strong></span>
                        <span>Available to Schedule: <strong id="pkg-info-left"></strong></span>
                    </div>
                    <div style="margin-top:5px; font:700 11.5px 'Nunito Sans'; color:#5A6B7E;" id="pkg-info-scheduled"></div>
                    <div style="margin-top:6px; font:700 11.5px 'Nunito Sans';" id="pkg-info-preview"></div>
                </div>

                <div id="f-repeat-bound-wrap">
                    <label class="f-label" for="f-occurrences">Number of sessions</label>
                    <input type="number" id="f-occurrences" class="f-input" min="1" max="520" placeholder="Leave blank to use all remaining package hours">
                </div>
                <div class="f-help" id="f-repeat-bound-help" style="margin-top:-8px;">Automatically calculated based on the patient’s remaining package hours and selected session duration. You can schedule all available sessions now or reduce the number to schedule only part of them and book the remaining sessions later.</div>

                <div style="display:flex; gap:10px;">
                    <div style="flex:1;">
                        <label class="f-label" for="f-duration">Duration</label>
                        <select id="f-duration" class="f-select">
                            @foreach ($durations as $d)
                                <option value="{{ $d }}" @selected($d === 60)>{{ $d }} min</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label class="f-label">Time</label>
                        <input type="hidden" id="f-start">
                        <button type="button" class="f-input f-time-trigger" id="f-time-trigger">
                            <span id="f-time-trigger-label">Select a time</span>
                            <span class="f-time-trigger-caret">▾</span>
                        </button>
                        <div class="time-grid" id="f-time-grid"></div>
                    </div>
                </div>

                <div>
                    <label class="f-label" for="f-activity">Activity</label>
                    <input type="text" id="f-activity" class="f-input" placeholder="e.g. supervision, team meeting" maxlength="120">
                </div>

                <div>
                    <label class="f-label">Type</label>
                    <div id="ms-types"></div>
                </div>

                <div>
                    <label class="f-label" for="f-status">Status</label>
                    <select id="f-status" class="f-select">
                        <option value="scheduled">Scheduled</option>
                        <option value="completed" id="f-status-completed" disabled hidden>Completed — automatic</option>
                        <option value="cancelled_late">Cancelled — late</option>
                        <option value="cancelled_notice">Cancelled — with notice</option>
                        <option value="cancelled_clinic">Cancelled — clinic</option>
                        <option value="no_show">No-show</option>
                    </select>
                </div>

                <div id="f-notice-wrap" style="display:none;">
                    <label class="f-label" for="f-notice">Notice given (hours)</label>
                    <input type="number" id="f-notice" class="f-input" min="0" max="720" step="any" placeholder="{{ (int) config('billing.cancel_policy.notice_hours') }}">
                    <div class="f-help" id="f-notice-help"></div>
                </div>

                <div class="f-help" id="f-status-help" style="display:none; margin-top:-8px;">Attendance isn’t logged by hand — this session completed automatically when its time ended. Change it only to record a no-show or a cancellation.</div>

                <div>
                    <label class="f-label" for="f-notes">Notes (optional)</label>
                    <textarea id="f-notes" rows="3" class="f-textarea" placeholder="e.g. bring token board, parent joining"></textarea>
                </div>

                <div id="f-supervision-info" style="display:none; background:#EDE7F5; border-radius:10px; padding:10px 12px;">
                    <span class="sup-badge">★ Supervised</span>
                    <div class="sup-note" id="f-supervision-text"></div>
                </div>

                <div style="display:flex; gap:10px; margin-top:4px;" id="panel-actions">
                    <button type="submit" class="btn-save" id="save-btn">Save</button>
                    <button type="button" class="btn-cancel" id="panel-cancel">Cancel</button>
                </div>
                <button type="button" class="btn-danger" id="panel-delete" style="display:none;">Remove session</button>
            </form>
        </div>
    </div>

    <!-- Mark leave -->
    <div id="leave-modal" class="cmodal-overlay">
        <div class="cmodal">
            <div class="cmodal-title">Mark leave</div>
            <div class="f-error" id="leave-error"></div>
            <div class="f-row">
                <div>
                    <label class="f-label" for="l-staff">Staff member</label>
                    <select id="l-staff" class="f-select"></select>
                </div>
                <div>
                    <label class="f-label" for="l-day">Day</label>
                    <select id="l-day" class="f-select"></select>
                </div>
            </div>
            <div>
                <label class="f-label" for="l-type">Leave type</label>
                <select id="l-type" class="f-select"></select>
            </div>
            <textarea id="l-reason" rows="3" class="f-textarea" placeholder="Reason (required) — e.g. approved annual leave, cover arranged with Ms. Fatima"></textarea>
            <div class="impact-box" id="l-impact">Checking booked sessions…</div>
            <div class="f-row">
                <button type="button" class="btn-save" id="l-submit">Mark on leave</button>
                <button type="button" class="btn-cancel" id="l-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Utilisation report -->
    <div id="util-modal" class="cmodal-overlay">
        <div class="cmodal wide">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                <div>
                    <div class="cmodal-title">Utilisation report</div>
                    <div class="cmodal-sub" id="util-sub" style="margin-top:2px;">Therapy hours delivered per week</div>
                </div>
                <button type="button" class="cal-btn" id="util-close">Close</button>
            </div>
            <div style="overflow-x:auto;"><table class="util-table" id="util-table"></table></div>
            <div class="f-help">Direct therapy hours only — supervision, admin time, leave and cancelled shifts are excluded.</div>
        </div>
    </div>

    <!-- Log supervision -->
    <div id="sup-modal" class="cmodal-overlay">
        <div class="cmodal">
            <div class="cmodal-title">Supervision note</div>
            <div class="cmodal-sub" id="sup-session-line"></div>
            <div class="cmodal-sub" id="sup-observer-line" style="margin-top:2px;"></div>
            <div class="f-error" id="sup-error"></div>
            <textarea id="sup-notes" rows="4" class="f-textarea" placeholder="What you observed (required) — e.g. Prompt fading on target; continue mand training at this level"></textarea>
            <div class="f-help">Shows on the calendar — and this is the only comment carried onto the invoice.</div>
            <div class="f-row">
                <button type="button" class="btn-save" id="sup-submit">Mark as supervised</button>
                <button type="button" class="btn-cancel" id="sup-cancel">Cancel</button>
            </div>
            <button type="button" class="btn-danger" id="sup-remove" style="display:none;">Remove supervision</button>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
    (function () {
        const root = document.getElementById('calendar-root');
        const STAFF = JSON.parse(root.dataset.staff);
        const LEADS = JSON.parse(root.dataset.leads);
        const TYPES = JSON.parse(root.dataset.types);
        const LEAVE_TYPES = JSON.parse(root.dataset.leaveTypes);
        const CAN_MANAGE = root.dataset.canManage === '1';
        // Cancellation terms, same source the invoice picker prices from, so
        // the two screens offer the same statuses and say the same thing.
        const CANCEL_POLICY = @json(config('billing.cancel_policy'));
        const NOTICE_STATES = ['cancelled_late', 'cancelled_notice'];
        // Status key (shared with billing) -> what the calendar API takes.
        const STATUS_WIRE = { scheduled: 'scheduled', no_show: 'no_show', cancelled_clinic: 'cancelled:clinic', cancelled_late: 'cancelled:family', cancelled_notice: 'cancelled:family' };
        const CAN_BOOK = root.dataset.canBook === '1';
        const FEED_URL = root.dataset.feedUrl;
        const LEADS_URL = root.dataset.leadsUrl;
        const STORE_URL = root.dataset.storeUrl;
        const BASE_URL = root.dataset.baseUrl;
        const UTIL_URL = root.dataset.utilisationUrl;
        const LEAVE_URL = root.dataset.leaveUrl;
        const LEAVE_IMPACT_URL = root.dataset.leaveImpactUrl;
        const CURRENT_USER_NAME = root.dataset.meName || '';
        const INITIAL_SESSION_ID = root.dataset.initialSessionId || null;
        const INITIAL_DATE = root.dataset.initialDate || null;
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

        const TYPE_COLORS = {
            'ABA':             { bg: '#F9E7EC', fg: '#C8355F' },
            'Speech':          { bg: '#E7EFF7', fg: '#24619C' },
            'OT':              { bg: '#F7EEDD', fg: '#B97F24' },
            'Assessment':      { bg: '#EDE7F5', fg: '#6E4FA8' },
            'Parent training': { bg: '#E3F1E9', fg: '#2E7D5B' },
        };
        const CATEGORY_COLORS = {
            off:         { bg: '#F5E3C0', fg: '#8A6A2B', label: 'Off' },
            covered:     { bg: '#DDF3E4', fg: '#1E7A46', label: 'Covered shift' },
            leave:       { bg: '#FDF3B0', fg: '#7A5C00', label: 'Leave/PH' },
            cancelled:   { bg: '#FBE1E1', fg: '#B3261E', label: 'Cancelled shift' },
            admin:       { bg: '#FBF3C7', fg: '#8A6D00', label: 'Admin time/Training' },
            observation: { bg: '#F3E4F7', fg: '#8E3B9C', label: 'Observation' },
            supervision: { bg: '#E4E0F7', fg: '#4B3F9E', label: 'Supervision' },
            free:        { bg: '#FBE9EE', fg: '#C8355F', label: 'Free slot' },
        };
        const NEUTRAL = { bg: '#F6F3EE', fg: '#5A6B7E' };

        function colorFor(s) {
            if (s.category && s.category !== 'therapy') return CATEGORY_COLORS[s.category] || NEUTRAL;
            return TYPE_COLORS[s.activity_type] || NEUTRAL;
        }

        // ---- State ----
        let view = 'day';
        let anchor = INITIAL_DATE || todayIso();
        let sessionsById = {};
        let leaves = [];
        let supervisionMode = false;

        // ---- Date helpers ----
        function todayIso() { return toIsoLocal(new Date()); }
        function parseIso(str) { return new Date(`${str}T00:00:00`); }
        function toIsoLocal(d) {
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        }
        function addDays(d, n) { const x = new Date(d); x.setDate(x.getDate() + n); return x; }
        function mondayOf(iso) {
            const d = parseIso(iso);
            const day = d.getDay();
            return addDays(d, day === 0 ? -6 : 1 - day);
        }
        function fmt(iso, opts) { return parseIso(iso).toLocaleDateString('en-GB', opts); }
        function escapeHtml(str) {
            return String(str ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }
        function staffById(id) { return STAFF.find(t => String(t.id) === String(id)); }

        async function api(url, options = {}) {
            const res = await fetch(url, {
                ...options,
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                    ...options.headers,
                },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const err = new Error(data.message || 'Request failed');
                err.errors = data.errors || {};
                throw err;
            }
            return data;
        }

        let toastTimer;
        function toast(msg) {
            const el = document.getElementById('toast');
            el.textContent = msg;
            el.style.display = 'block';
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => { el.style.display = 'none'; }, 3200);
        }

        // ---- Legend ----
        document.getElementById('cal-legend').innerHTML = Object.values(CATEGORY_COLORS)
            .map(c => `<span class="legend-chip" style="background:${c.bg}; color:${c.fg};">${c.label}</span>`).join('');

        // ---- Feed ----
        async function loadRange(start, end) {
            const data = await api(`${FEED_URL}?start=${start}&end=${end}`);
            sessionsById = {};
            (data.sessions || []).forEach(s => { sessionsById[s.id] = s; });
            leaves = data.leaves || [];
            return data.sessions || [];
        }
        function leaveFor(userId, iso) {
            return leaves.find(l => String(l.user_id) === String(userId) && l.leave_date === iso);
        }

        // ---- Top bar ----
        function renderTop() {
            const title = document.getElementById('cal-title');
            const sub = document.getElementById('cal-subtitle');
            document.querySelectorAll('.cal-view-btn').forEach(b => b.classList.toggle('active', b.dataset.view === view));
            document.getElementById('nav-day').style.display = view === 'day' ? 'flex' : 'none';
            document.getElementById('nav-range').style.display = view === 'day' ? 'none' : 'flex';

            const monday = mondayOf(anchor);
            const sunday = addDays(monday, 6);
            const thisWeek = todayIso() >= toIsoLocal(monday) && todayIso() <= toIsoLocal(sunday);

            if (view === 'day') {
                title.textContent = `Calendar — ${fmt(anchor, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}`;
                sub.textContent = CAN_MANAGE
                    ? 'Drag a card to another therapist to reassign · click Edit to modify'
                    : 'Click a card to view it · only the Supervisor changes the schedule';
                const pick = document.getElementById('day-pick');
                pick.innerHTML = '';
                for (let i = 0; i < 7; i++) {
                    const d = addDays(monday, i);
                    const iso = toIsoLocal(d);
                    const opt = document.createElement('option');
                    opt.value = iso;
                    opt.textContent = d.toLocaleDateString('en-GB', { weekday: 'short' });
                    opt.selected = iso === anchor;
                    pick.appendChild(opt);
                }
            } else if (view === 'week') {
                title.textContent = thisWeek ? 'Calendar — this week' : `Calendar — week of ${monday.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })}`;
                sub.textContent = 'Staff roster grid · click a session to edit, + to book';
                document.getElementById('nav-range-label').textContent =
                    `${monday.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })} – ${sunday.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}`;
            } else {
                title.textContent = `Calendar — ${fmt(anchor, { month: 'long', year: 'numeric' })}`;
                sub.textContent = 'Click a date to open that day';
                document.getElementById('nav-range-label').textContent = fmt(anchor, { month: 'long', year: 'numeric' });
            }

            if (supervisionMode) sub.textContent = 'Click a finished session to add a supervision note';
            document.getElementById('calendar-root').classList.toggle('sup-mode', supervisionMode);
        }

        // ---- Day view ----
        function cardHtml(s) {
            const c = colorFor(s);
            const cancelled = s.status === 'cancelled';
            const meta = [s.activity_types && s.activity_types.length > 1 ? s.activity_types.join(' · ') : s.activity_type, s.room].filter(Boolean).join(' · ');
            const sup = s.supervised
                ? `<div><span class="sup-badge">★ Supervised</span><div class="sup-note">${escapeHtml(s.supervision_notes || '')}</div></div>` : '';
            const cover = s.cover_for_name ? `<div class="card-meta" style="color:${c.fg};">Cover for ${escapeHtml(s.cover_for_name)}</div>` : '';
            const status = cancelled || s.status === 'no_show' ? `<div class="card-meta" style="color:${c.fg}; font-weight:800;">${escapeHtml(s.status_label)}</div>` : '';
            const hint = CAN_MANAGE && !cancelled ? '<div class="card-hint">Drag onto another therapist to reassign</div>' : '';
            return `
                <div class="card-top">
                    <div class="card-time" style="color:${c.fg};">${escapeHtml(s.start_time)}–${escapeHtml(s.end_time)}</div>
                    <div class="card-duration">${escapeHtml(s.duration_minutes)} min</div>
                </div>
                <div class="card-name">${escapeHtml(s.patient_name)}</div>
                <div class="card-meta">${escapeHtml(meta)}</div>
                ${cover}${status}${sup}${hint}
                <button type="button" class="card-btn card-edit">${CAN_MANAGE ? 'Edit' : 'View'}</button>
            `;
        }

        function supervisionClasses(s) {
            if (!supervisionMode) return '';
            return s.can_supervise ? ' sup-pick' : ' sup-dim';
        }

        function bindSessionEl(el, s) {
            el.addEventListener('click', (e) => {
                if (supervisionMode) {
                    e.preventDefault();
                    if (s.can_supervise) openSupervision(s);
                    return;
                }
                if (e.target.closest('.card-edit') || !e.target.closest('.card-btn')) openPanel({ session: s });
            });
        }

        let dayRenderSeq = 0;

        async function renderDay() {
            renderTop();
            const grid = document.getElementById('calendar-grid');

            // Two renders can be in flight at once - a second date click, or a
            // refresh landing on top of one already loading. Emptying the grid
            // before the await meant both cleared first and both appended their
            // columns afterwards, so every therapist was listed twice. Clear
            // only once the data is in hand, and drop any render a newer one
            // has overtaken. renderWeek and renderMonth already clear after
            // their await, so only this one was affected.
            const seq = ++dayRenderSeq;
            const sessions = (await loadRange(anchor, anchor)).sort((a, b) => a.start_time.localeCompare(b.start_time));
            if (seq !== dayRenderSeq) return;

            grid.innerHTML = '';

            STAFF.forEach(t => {
                const mine = sessions.filter(s => String(s.therapist_id) === String(t.id));
                const leave = leaveFor(t.id, anchor);
                const col = document.createElement('div');
                col.className = 'therapist-col';
                col.dataset.therapistId = t.id;
                col.innerHTML = `
                    <div class="therapist-col-header">
                        <div class="therapist-col-name">${escapeHtml(t.name)}</div>
                        <div class="therapist-col-sub">${escapeHtml(t.designation)} · ${mine.length} session${mine.length === 1 ? '' : 's'}</div>
                    </div>
                    <div class="col-body"></div>`;
                const body = col.querySelector('.col-body');

                if (leave) {
                    const lc = document.createElement('div');
                    lc.className = 'leave-card';
                    lc.innerHTML = `<div class="leave-card-title">On leave · ${escapeHtml(leave.leave_type)}</div><div class="leave-card-reason">${escapeHtml(leave.reason)}</div>`;
                    body.appendChild(lc);
                }

                if (!mine.length && !leave) {
                    body.innerHTML = '<div class="col-empty">No sessions</div>';
                }

                mine.forEach(s => {
                    const card = document.createElement('div');
                    const c = colorFor(s);
                    card.className = 'session-card' + (s.status === 'cancelled' ? ' is-cancelled' : '') + supervisionClasses(s);
                    card.style.background = c.bg;
                    card.dataset.sessionId = s.id;
                    card.innerHTML = cardHtml(s);
                    if (CAN_MANAGE && s.status !== 'cancelled' && !supervisionMode) {
                        card.setAttribute('draggable', 'true');
                        card.addEventListener('dragstart', e => e.dataTransfer.setData('text/plain', String(s.id)));
                    }
                    bindSessionEl(card, s);
                    body.appendChild(card);
                });

                if (CAN_MANAGE) {
                    col.addEventListener('dragover', e => { e.preventDefault(); col.classList.add('drag-over'); });
                    col.addEventListener('dragleave', () => col.classList.remove('drag-over'));
                    col.addEventListener('drop', e => {
                        e.preventDefault();
                        col.classList.remove('drag-over');
                        reassign(e.dataTransfer.getData('text/plain'), t.id);
                    });
                }
                grid.appendChild(col);
            });
        }

        async function reassign(sessionId, therapistId) {
            const s = sessionsById[sessionId];
            if (!s || String(s.therapist_id) === String(therapistId)) return;
            try {
                await api(`${BASE_URL}/${sessionId}`, { method: 'PUT', body: JSON.stringify({ therapist_id: therapistId }) });
                toast('Session reassigned.');
            } catch (e) {
                toast(e.message);
            }
            refresh();
        }

        // ---- Week view: roster ----
        const DOW = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        async function renderWeek() {
            renderTop();
            const monday = mondayOf(anchor);
            const days = Array.from({ length: 7 }, (_, i) => toIsoLocal(addDays(monday, i)));
            const sessions = (await loadRange(days[0], days[6])).sort((a, b) => a.start_time.localeCompare(b.start_time));
            const today = todayIso();

            const table = document.getElementById('roster');
            table.innerHTML = '';

            const head = document.createElement('tr');
            head.innerHTML = '<th>No.</th><th>Name</th><th>Designation</th>' + days.map((iso, i) => {
                const d = parseIso(iso);
                const isToday = iso === today;
                return `<th class="day${isToday ? ' today' : ''}">${DOW[i]}<small>${d.getDate()}-${d.toLocaleDateString('en-GB', { month: 'short' })}${isToday ? ' · today' : ''}</small></th>`;
            }).join('');
            table.appendChild(head);

            STAFF.forEach((t, idx) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td class="no">${idx + 1}</td><td class="name">${escapeHtml(t.name)}</td><td class="desig">${escapeHtml(t.designation)}</td>`;

                days.forEach((iso, i) => {
                    const td = document.createElement('td');
                    const weekend = i >= 5;
                    const cell = sessions.filter(s => String(s.therapist_id) === String(t.id) && s.session_date === iso);
                    const leave = leaveFor(t.id, iso);
                    if (iso === today) td.classList.add('today-col');

                    if (weekend && !cell.length && !leave) {
                        td.className = 'off';
                        td.textContent = 'OFF';
                        tr.appendChild(td);
                        return;
                    }
                    if (weekend) td.style.background = CATEGORY_COLORS.off.bg;

                    if (leave) {
                        const lb = document.createElement('div');
                        lb.className = 'blk';
                        lb.style.background = CATEGORY_COLORS.leave.bg;
                        lb.style.color = CATEGORY_COLORS.leave.fg;
                        lb.title = leave.reason;
                        lb.innerHTML = `<div class="blk-time">All day</div><div class="blk-name">${escapeHtml(leave.leave_type)}</div>`;
                        td.appendChild(lb);
                    }

                    cell.forEach(s => {
                        const c = colorFor(s);
                        const b = document.createElement('div');
                        b.className = 'blk' + (s.status === 'cancelled' ? ' is-cancelled' : '') + supervisionClasses(s);
                        b.style.background = c.bg;
                        b.style.color = c.fg;
                        b.title = `${s.patient_name} · ${s.activity_type} · ${s.status_label}`;
                        const name = s.cover_for_name ? `${s.patient_name} (cover)` : s.patient_name;
                        b.innerHTML = `<div class="blk-time">${escapeHtml(s.start_time)}</div><div class="blk-name">${escapeHtml(name)}</div>`
                            + (s.supervised ? `<span class="sup-badge">★ Supervised</span><div class="sup-note">${escapeHtml(s.supervision_notes || '')}</div>` : '');
                        bindSessionEl(b, s);
                        td.appendChild(b);
                    });

                    if (!cell.length && !leave && !weekend) {
                        td.insertAdjacentHTML('beforeend', '<span class="free-slot">Free slot</span><br>');
                    }

                    if (CAN_BOOK) {
                        const add = document.createElement('span');
                        add.className = 'cell-add';
                        add.textContent = '+';
                        add.title = 'Book a session';
                        add.addEventListener('click', () => openPanel({ therapistId: t.id, date: iso }));
                        td.appendChild(add);
                    }
                    tr.appendChild(td);
                });
                table.appendChild(tr);
            });
        }

        // ---- Month view ----
        async function renderMonth() {
            renderTop();
            const d = parseIso(anchor);
            const first = new Date(d.getFullYear(), d.getMonth(), 1);
            const last = new Date(d.getFullYear(), d.getMonth() + 1, 0);
            const sessions = (await loadRange(toIsoLocal(first), toIsoLocal(last))).sort((a, b) => a.start_time.localeCompare(b.start_time));
            const byDay = {};
            sessions.forEach(s => { (byDay[s.session_date] ||= []).push(s); });

            const grid = document.getElementById('month-grid');
            grid.innerHTML = '';
            DOW.forEach(x => { const e = document.createElement('div'); e.className = 'month-dow'; e.textContent = x; grid.appendChild(e); });

            const lead = first.getDay() === 0 ? 6 : first.getDay() - 1;
            for (let i = 0; i < lead; i++) { const b = document.createElement('div'); b.className = 'month-cell is-empty'; grid.appendChild(b); }

            const today = todayIso();
            for (let day = 1; day <= last.getDate(); day++) {
                const cd = new Date(first.getFullYear(), first.getMonth(), day);
                const iso = toIsoLocal(cd);
                const list = byDay[iso] || [];
                const cell = document.createElement('div');
                const weekend = cd.getDay() === 0 || cd.getDay() === 6;
                cell.className = 'month-cell' + (iso === today ? ' is-today' : '') + (weekend ? ' is-weekend' : '');
                const preview = list.slice(0, 3).map(s => {
                    const c = colorFor(s);
                    return `<div class="month-chip${s.status === 'cancelled' ? ' is-cancelled' : ''}" style="background:${c.bg}; color:${c.fg};"><span class="month-chip-time">${escapeHtml(s.start_time)}</span> ${escapeHtml(s.patient_name)}</div>`;
                }).join('');
                cell.innerHTML = `
                    <div class="month-cell-top"><span class="month-cell-date">${day}</span>${list.length ? `<span class="month-cell-count">${list.length}</span>` : ''}</div>
                    ${preview}${list.length > 3 ? `<div class="month-more">+${list.length - 3} more</div>` : ''}
                    ${CAN_BOOK ? '<span class="month-book-link">+ Book</span>' : ''}`;
                if (CAN_BOOK) cell.querySelector('.month-book-link').addEventListener('click', e => { e.stopPropagation(); openPanel({ date: iso }); });
                cell.addEventListener('click', () => { anchor = iso; switchView('day'); });
                grid.appendChild(cell);
            }
        }

        function refresh() {
            if (view === 'week') return renderWeek();
            if (view === 'month') return renderMonth();
            return renderDay();
        }

        function switchView(v) {
            view = v;
            document.getElementById('day-view').style.display = v === 'day' ? 'flex' : 'none';
            document.getElementById('week-view').style.display = v === 'week' ? 'flex' : 'none';
            document.getElementById('month-view').style.display = v === 'month' ? 'flex' : 'none';
            refresh();
        }

        document.querySelectorAll('.cal-view-btn').forEach(b => b.addEventListener('click', () => switchView(b.dataset.view)));
        document.getElementById('day-pick').addEventListener('change', e => { anchor = e.target.value; renderDay(); });
        document.querySelectorAll('.cal-nav-arrow').forEach(b => b.addEventListener('click', () => {
            const dir = parseInt(b.dataset.nav, 10);
            const d = parseIso(anchor);
            if (view === 'day') anchor = toIsoLocal(addDays(d, dir));
            else if (view === 'week') anchor = toIsoLocal(addDays(d, 7 * dir));
            else anchor = toIsoLocal(new Date(d.getFullYear(), d.getMonth() + dir, 1));
            refresh();
        }));

        // ---- Multi-select component ----
        function createMultiSelect(host, opts) {
            const state = { items: [...opts.items], selected: [] };
            host.classList.add('ms');
            host.innerHTML = `
                <div class="ms-chips"></div>
                <button type="button" class="ms-trigger placeholder"><span class="ms-label">${escapeHtml(opts.placeholder)}</span><span class="caret">▼</span></button>
                <div class="ms-pop"><input type="text" class="ms-search" placeholder="${escapeHtml(opts.searchPlaceholder || 'Search…')}"><div class="ms-list"></div></div>
                ${opts.allowCustom ? `<div class="ms-custom"><input type="text" placeholder="${escapeHtml(opts.customPlaceholder || '+ Add custom')}" maxlength="120"><button type="button">Add</button></div>` : ''}`;
            const chips = host.querySelector('.ms-chips');
            const trigger = host.querySelector('.ms-trigger');
            const label = host.querySelector('.ms-label');
            const pop = host.querySelector('.ms-pop');
            const search = host.querySelector('.ms-search');
            const list = host.querySelector('.ms-list');
            let disabled = false;

            function render() {
                chips.innerHTML = state.selected.map(item =>
                    `<span class="ms-chip">${escapeHtml(item.name)}${disabled ? '' : `<button type="button" data-id="${escapeHtml(item.id)}" aria-label="Remove">✕</button>`}</span>`).join('');
                chips.querySelectorAll('button').forEach(b => b.addEventListener('click', () => { deselect(b.dataset.id); }));
                const n = state.selected.length;
                label.textContent = n === 0 ? opts.placeholder : (n === 1 ? state.selected[0].name : `${n} selected`);
                trigger.classList.toggle('placeholder', n === 0);
                const term = search.value.trim().toLowerCase();
                const visible = state.items.filter(i => !term || i.name.toLowerCase().includes(term));
                list.innerHTML = visible.length ? visible.map(i => {
                    const sel = state.selected.some(s => String(s.id) === String(i.id));
                    return `<label class="ms-opt${sel ? ' sel' : ''}"><input type="${opts.single ? 'radio' : 'checkbox'}" ${sel ? 'checked' : ''} data-id="${escapeHtml(i.id)}"> ${escapeHtml(i.name)}</label>`;
                }).join('') : '<div class="ms-empty">No matches</div>';
                list.querySelectorAll('input').forEach(inp => inp.addEventListener('change', () => toggle(inp.dataset.id)));
                opts.onChange && opts.onChange(state.selected);
            }
            function toggle(id) {
                const item = state.items.find(i => String(i.id) === String(id));
                if (!item) return;
                const idx = state.selected.findIndex(s => String(s.id) === String(id));
                if (idx >= 0) state.selected.splice(idx, 1);
                else if (opts.single) state.selected = [item];
                else state.selected.push(item);
                render();
            }
            function deselect(id) {
                state.selected = state.selected.filter(s => String(s.id) !== String(id));
                render();
            }
            trigger.addEventListener('click', () => {
                if (disabled) return;
                const open = !pop.classList.contains('open');
                document.querySelectorAll('.ms-pop.open').forEach(p => { p.classList.remove('open'); p.previousElementSibling.classList.remove('open'); });
                pop.classList.toggle('open', open);
                trigger.classList.toggle('open', open);
                if (open) { search.value = ''; render(); search.focus(); }
            });
            search.addEventListener('input', render);
            document.addEventListener('click', e => {
                if (!host.contains(e.target)) { pop.classList.remove('open'); trigger.classList.remove('open'); }
            });
            if (opts.allowCustom) {
                const input = host.querySelector('.ms-custom input');
                const btn = host.querySelector('.ms-custom button');
                const addCustom = () => {
                    const name = input.value.trim();
                    if (!name) return;
                    const existing = state.items.find(i => i.name.toLowerCase() === name.toLowerCase());
                    const item = existing || { id: `custom:${name}`, name, custom: true };
                    if (!existing) state.items.push(item);
                    if (!state.selected.some(s => String(s.id) === String(item.id))) {
                        if (opts.single) state.selected = [item]; else state.selected.push(item);
                    }
                    input.value = '';
                    render();
                };
                btn.addEventListener('click', addCustom);
                input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); addCustom(); } });
            }
            render();
            return {
                getSelected: () => [...state.selected],
                setSelected(ids) {
                    state.selected = ids.map(id => state.items.find(i => String(i.id) === String(id))).filter(Boolean);
                    render();
                },
                addItem(item) { if (!state.items.some(i => String(i.id) === String(item.id))) state.items.push(item); },
                setSingle(single) { opts.single = single; if (single && state.selected.length > 1) state.selected = [state.selected[0]]; render(); },
                setDisabled(v) { disabled = v; trigger.disabled = v; host.querySelectorAll('.ms-custom input, .ms-custom button').forEach(el => el.disabled = v); render(); },
                clear() { state.selected = []; render(); },
            };
        }

        const therapistOpts = {
            items: STAFF, placeholder: 'Select a therapist…', searchPlaceholder: 'Search therapists…',
            // One therapist per booking - to give a session to someone else
            // too, book it again for them. Keeps hours easy to track back to
            // a single therapist/patient pair instead of splitting silently
            // across several.
            single: true,
            // Deferred: createMultiSelect() fires this once synchronously while
            // still constructing itself, before the `msTherapists` const below
            // finishes being assigned - refreshDayBookings() reads
            // msTherapists.getSelected(), so it must run after this call stack
            // (and that assignment) completes.
            onChange() { setTimeout(() => { refreshDayBookings(); updatePackageInfo(); }, 0); },
        };
        const msTherapists = createMultiSelect(document.getElementById('ms-therapists'), therapistOpts);
        const patientOpts = {
            items: LEADS, placeholder: 'Select a patient…', searchPlaceholder: 'Search patients…',
            // One patient per booking too, for the same reason - book again
            // for a second patient rather than grouping them into one slot.
            single: true,
            allowCustom: true, customPlaceholder: '+ Add custom patient',
            // Deferred for the same reason as therapistOpts.onChange above:
            // render() fires this once synchronously while createMultiSelect()
            // is still building the `msPatients` instance it will return, so
            // calling updatePackageInfo() (which reads msPatients/msTypes/
            // msWeekdays) right here would hit those consts before they're
            // initialized.
            onChange() { setTimeout(updatePackageInfo, 0); },
        };
        const msPatients = createMultiSelect(document.getElementById('ms-patients'), patientOpts);
        const msTypes = createMultiSelect(document.getElementById('ms-types'), {
            items: TYPES.map(t => ({ id: t, name: t })), placeholder: 'Select type(s)…', searchPlaceholder: 'Search types…',
            allowCustom: true, customPlaceholder: '+ Add custom type',
            onChange(sel) {
                const c = sel.length ? (TYPE_COLORS[sel[0].name] || CATEGORY_COLORS[({ Supervision: 'supervision', Observation: 'observation', 'Admin time': 'admin', Training: 'admin' })[sel[0].name]] || NEUTRAL) : { fg: '#C8355F' };
                document.getElementById('panel-inner').style.borderLeftColor = c.fg;
                document.getElementById('save-btn').style.background = c.fg;
                setTimeout(updatePackageInfo, 0);
            },
        });

        // ---- Panel ----
        const panel = document.getElementById('session-panel');
        const form = document.getElementById('session-form');
        const repeats = document.getElementById('f-repeats');
        let panelBaseDate = anchor; // the date the booking was opened from ("+ Book", a roster cell, or the current day)
        // Whether staff have typed their own value into "Number of sessions" -
        // once they have, updatePackageInfo() stops overwriting it with the
        // auto-calculated count. Clearing the field by hand turns auto-fill
        // back on. Reset whenever a fresh panel opens.
        let occurrencesUserEdited = false;

        // "Every week" books a weekday slot (first occurrence on/after the date
        // the panel was opened from); "One-off on a date" books a single date.
        const weekdayOpts = {
            items: [1, 2, 3, 4, 5, 6, 0].map(d => ({ id: d, name: DOW[(d + 6) % 7] })),
            placeholder: 'Select day(s)…', searchPlaceholder: 'Search days…',
            // Deferred - same reason as patientOpts.onChange above (fires
            // synchronously during msWeekdays's own construction).
            onChange() { setTimeout(updatePackageInfo, 0); },
        };
        const msWeekdays = createMultiSelect(document.getElementById('ms-weekdays'), weekdayOpts);

        // How many sessions the matched package's remaining hours allow at the
        // currently-chosen Duration/Therapists - the same number "using
        // patient hours" auto-books (mirrors packageSessionBudget() on the
        // server exactly). Independent of which weekday(s) are picked - that
        // only decides how the count is spread across dates, not the count
        // itself. Null when there's nothing to size against (no package
        // covers the selected type, or no duration chosen yet).
        function autoSessionCount(packageLeftHours) {
            if (packageLeftHours == null) return null;
            const duration = parseInt(document.getElementById('f-duration').value, 10) || 0;
            const therapistCount = Math.max(1, msTherapists.getSelected().length);
            const perOccurrence = duration * therapistCount;
            if (!perOccurrence) return null;
            return Math.floor((packageLeftHours * 60) / perOccurrence);
        }

        // How many occurrences the current Repeats/Count inputs (plus, when no
        // explicit count is given, autoSessionCount() above) work out to -
        // mirrors the server's own sizing in store() for a live preview.
        function weeklyOccurrenceEstimate(packageLeftHours) {
            if (repeats.value !== 'weekly') return 1;
            const occ = parseInt(document.getElementById('f-occurrences').value, 10);
            if (occ) return occ;
            return autoSessionCount(packageLeftHours);
        }

        function formatSessionWhen(session) {
            if (!session) return '';
            const d = new Date(session.date + 'T00:00:00');
            const dateLabel = d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
            const [h, m] = session.time.split(':').map(Number);
            const period = h >= 12 ? 'PM' : 'AM';
            const h12 = (h % 12) || 12;
            return `${dateLabel}, ${h12}:${String(m).padStart(2, '0')} ${period}`;
        }

        // "Already scheduled — next: Sep 19, 10:00 AM · last: Oct 28, 10:00 AM
        // (+27 more upcoming)" or "Not yet scheduled" - from LEADS[]'s
        // upcoming_count/next_session/last_session (every still-`scheduled`,
        // not-yet-past session on the calendar for this patient, regardless
        // of activity type or package). last_session is only present when
        // there's more than one upcoming session (otherwise it's the same as
        // next_session, so there's nothing extra to show).
        function scheduledStatusText(lead) {
            if (!lead || !lead.upcoming_count) {
                return 'Not yet scheduled — no upcoming sessions.';
            }

            const next = formatSessionWhen(lead.next_session);
            const last = lead.last_session ? ` · last: ${formatSessionWhen(lead.last_session)}` : '';
            const more = lead.upcoming_count > 1 ? ` (+${lead.upcoming_count - 1} more upcoming)` : '';

            return `Already scheduled — next: ${next}${last}${more}`;
        }

        // LEADS is only ever a snapshot from when the page was rendered - a
        // session booked earlier in this same visit (by this user, or by
        // anyone else) wouldn't show as "used" here without this. Mutates
        // LEADS in place (never reassigns the const) so every existing
        // reference to it - including inside createMultiSelect()'s own
        // captured `items` list - keeps working.
        let leadsRefreshInFlight = null;
        function refreshLeads() {
            if (!LEADS_URL) return Promise.resolve();
            if (leadsRefreshInFlight) return leadsRefreshInFlight;

            leadsRefreshInFlight = api(LEADS_URL)
                .then(data => {
                    if (Array.isArray(data.leads)) {
                        LEADS.length = 0;
                        LEADS.push(...data.leads);
                    }
                })
                .catch(() => { /* stale-but-present data beats none on a transient failure */ })
                .finally(() => { leadsRefreshInFlight = null; });

            return leadsRefreshInFlight;
        }

        // The Package info card (Patient / Service / Package / Total / Used /
        // Remaining) plus a live "this books Xh -> Yh left" preview, read from
        // LEADS[].packages (each package pre-tagged server-side with the
        // activity_type codes its service matches). Purely informational -
        // packageOverbookError() on the server is what actually enforces the
        // cap, this just lets staff see it before they hit Save.
        //
        // Shows as soon as a single patient is picked - staff need to see the
        // package the family already agreed to before they've necessarily
        // chosen a Type yet. Once a Type is also picked, the card narrows to
        // just the package(s) that cover it and adds the "this books Xh"
        // preview for that specific booking.
        function updatePackageInfo() {
            const box = document.getElementById('pkg-info');
            const patients = msPatients.getSelected();

            if (patients.length !== 1 || patients[0].custom) {
                box.style.display = 'none';
                return;
            }

            const lead = LEADS.find(l => String(l.id) === String(patients[0].id));
            const allPackages = (lead && lead.packages) || [];
            const previewEl = document.getElementById('pkg-info-preview');

            if (!allPackages.length) {
                document.getElementById('pkg-info-title').textContent = `${lead ? lead.name : 'Patient'} — no package on file`;
                document.getElementById('pkg-info-total').textContent = '—';
                document.getElementById('pkg-info-used').textContent = '—';
                document.getElementById('pkg-info-left').textContent = '—';
                document.getElementById('pkg-info-scheduled').textContent = scheduledStatusText(lead);
                previewEl.style.color = '#5A6B7E';
                previewEl.textContent = '';
                box.style.display = 'block';
                return;
            }

            const types = msTypes.getSelected();
            const type = types[0] && types[0].name;
            const matches = type ? allPackages.filter(p => (p.types || []).includes(type)) : [];
            const showing = matches.length ? matches : allPackages;

            const fmt = n => (Math.round(n * 10) / 10).toString();
            const fmtHours = n => `${fmt(n)} ${fmt(n) === '1' ? 'hour' : 'hours'}`;
            const total = showing.reduce((s, p) => s + p.total, 0);
            const used = showing.reduce((s, p) => s + p.used, 0);
            const left = Math.max(0, total - used);

            // Fill "Number of sessions" with what the package's remaining
            // hours allow at this Duration, as soon as a patient with a
            // package is picked - even before a Type is chosen (using the
            // combined total across all their packages until a Type narrows
            // it to the one(s) that actually cover it). Staff see the count
            // right away instead of only in the preview text below, and can
            // still edit/override it (occurrencesUserEdited then stops this
            // from overwriting their own value).
            const isNewBooking = !document.getElementById('f-id').value;
            if (isNewBooking && repeats.value === 'weekly' && !occurrencesUserEdited) {
                const auto = autoSessionCount(left);
                document.getElementById('f-occurrences').value = (auto != null && auto > 0) ? auto : '';
            }

            document.getElementById('pkg-info-title').textContent = `${lead.name} — ${showing.map(p => p.name).join(' + ')}`;
            document.getElementById('pkg-info-total').textContent = fmtHours(total);
            document.getElementById('pkg-info-used').textContent = fmtHours(used);
            document.getElementById('pkg-info-left').textContent = fmtHours(left);
            document.getElementById('pkg-info-scheduled').textContent = scheduledStatusText(lead);

            if (type && !matches.length) {
                // A type IS picked but nothing on file covers it - only then
                // is there truly nothing to size a booking against.
                previewEl.style.color = repeats.value === 'weekly' ? '#C8355F' : '#5A6B7E';
                previewEl.textContent = repeats.value === 'weekly'
                    ? `No package on file covers "${type}" — enter a number of sessions (there's nothing to size this automatically).`
                    : `No package on file covers "${type}" — this booking won't be capped against a package.`;
            } else {
                const duration = (parseInt(document.getElementById('f-duration').value, 10) || 0) / 60;
                const therapistCount = Math.max(1, msTherapists.getSelected().length);
                const occurrences = weeklyOccurrenceEstimate(left);

                if (occurrences === null) {
                    previewEl.style.color = '#5A6B7E';
                    previewEl.textContent = 'Pick a duration to preview hours used.';
                } else if (occurrences === 0 && !occurrencesUserEdited) {
                    previewEl.style.color = '#C8355F';
                    previewEl.textContent = 'All allocated hours have been scheduled' + (type ? ' for this type' : '') + ' — nothing will be booked automatically.';
                } else {
                    const requested = duration * therapistCount * occurrences;
                    const remaining = left - requested;
                    previewEl.style.color = remaining < 0 ? '#C8355F' : '#5A6B7E';
                    previewEl.textContent = remaining < 0
                        ? `This books ${fmtHours(requested)} — exceeds what's left by ${fmtHours(-remaining)}.`
                        : (occurrencesUserEdited
                            ? `This books ${fmtHours(requested)} → ${fmtHours(remaining)} will remain.`
                            : `Using patient hours: books ${occurrences} session${occurrences === 1 ? '' : 's'} (${fmtHours(requested)}) → ${fmtHours(remaining)} will remain.`);
                }
            }

            box.style.display = 'block';
        }

        function syncRepeatsUi(editing) {
            const weekly = !editing && repeats.value === 'weekly';
            document.getElementById('f-weekdays-wrap').style.display = weekly ? '' : 'none';
            // The Date field stays visible either way now - for a weekly
            // booking it's the series' start date, staff pick which
            // weekday(s) to repeat on separately, right below.
            document.getElementById('f-day-label').textContent = weekly ? 'Start date' : 'Date';
            document.getElementById('f-repeat-bound-wrap').style.display = weekly ? 'block' : 'none';
            document.getElementById('f-repeat-bound-help').style.display = weekly ? '' : 'none';
            updatePackageInfo();
        }
        repeats.addEventListener('change', () => syncRepeatsUi(false));
        document.getElementById('f-occurrences').addEventListener('input', () => {
            // A real keystroke, not our own auto-fill (setting .value in JS
            // doesn't fire 'input') - clearing the field by hand turns
            // auto-fill back on instead of leaving it stuck off.
            occurrencesUserEdited = document.getElementById('f-occurrences').value !== '';
            updatePackageInfo();
        });

        // ---- Cancellation status: the hours of notice decide whether the
        // session is chargeable, so the box and the dropdown move together.
        function defaultNotice(key) { return key === 'cancelled_notice' ? CANCEL_POLICY.notice_hours : Math.max(0, CANCEL_POLICY.notice_hours - 1); }
        function statusKey(s) {
            if (s.status !== 'cancelled') return s.status;
            if (s.cancel_reason === 'clinic') return 'cancelled_clinic';
            const n = s.cancel_notice_hours;
            return n === null || n === undefined || Number(n) >= CANCEL_POLICY.notice_hours ? 'cancelled_notice' : 'cancelled_late';
        }
        function noticeRule(hours) {
            if (hours === '' || hours === null || isNaN(hours)) return '';
            return Number(hours) >= CANCEL_POLICY.notice_hours
                ? `${CANCEL_POLICY.notice_hours} h or more notice — not charged.`
                : `Less than ${CANCEL_POLICY.notice_hours} h notice — ${CANCEL_POLICY.late_pct}% of the session is charged.`;
        }
        function syncNoticeUi() {
            const key = document.getElementById('f-status').value;
            const input = document.getElementById('f-notice');
            const isNotice = NOTICE_STATES.includes(key);
            document.getElementById('f-notice-wrap').style.display = isNotice ? '' : 'none';
            if (!isNotice) { input.value = ''; return; }
            if (input.value === '') input.value = defaultNotice(key);
            document.getElementById('f-notice-help').textContent = noticeRule(input.value);
        }
        document.getElementById('f-status').addEventListener('change', () => {
            const key = document.getElementById('f-status').value;
            document.getElementById('f-notice').value = NOTICE_STATES.includes(key) ? defaultNotice(key) : '';
            syncNoticeUi();
        });
        document.getElementById('f-notice').addEventListener('input', () => {
            const input = document.getElementById('f-notice');
            // The hours are what decide the charge, so they move the status
            // rather than sit under one that contradicts them.
            if (input.value !== '' && Number(input.value) >= 0) {
                document.getElementById('f-status').value = Number(input.value) >= CANCEL_POLICY.notice_hours ? 'cancelled_notice' : 'cancelled_late';
            }
            document.getElementById('f-notice-help').textContent = noticeRule(input.value);
        });

        function setFormDisabled(disabled) {
            form.querySelectorAll('input, select, textarea').forEach(el => { if (el.id !== 'f-id') el.disabled = disabled; });
            msTherapists.setDisabled(disabled); msPatients.setDisabled(disabled); msTypes.setDisabled(disabled); msWeekdays.setDisabled(disabled);
            document.getElementById('f-time-trigger').classList.toggle('is-disabled', disabled);
            document.getElementById('save-btn').style.display = disabled ? 'none' : '';
            document.getElementById('panel-cancel').textContent = disabled ? 'Close' : 'Cancel';
        }

        // ---- Start-time grid: slots are spaced by the picked duration itself
        // (60 min -> 8:00, 9:00, 10:00...; 90 min -> 8:00, 9:30, 11:00...), so
        // they're always back-to-back bookable slots, never overlapping.
        const DAY_OPEN_MIN = 8 * 60;   // 08:00
        const DAY_CLOSE_MIN = 19 * 60; // 19:00

        function minutesToLabel(mins) {
            const h24 = Math.floor(mins / 60), m = mins % 60;
            const period = h24 >= 12 ? 'PM' : 'AM';
            let h12 = h24 % 12; if (h12 === 0) h12 = 12;
            return `${h12}:${String(m).padStart(2, '0')} ${period}`;
        }
        function minutesToValue(mins) {
            return `${String(Math.floor(mins / 60)).padStart(2, '0')}:${String(mins % 60).padStart(2, '0')}`;
        }
        function valueToMinutes(value) {
            if (!value) return null;
            const [h, m] = value.split(':').map(Number);
            return h * 60 + m;
        }

        function closeTimeGrid() {
            document.getElementById('f-time-grid').classList.remove('open');
            document.getElementById('f-time-trigger').classList.remove('open');
        }

        // The date whose bookings the grid should check for conflicts - the
        // Date field is always visible now (it's also the recurring series'
        // start date, not just the one-off date), so this is just its value.
        function timeGridReferenceDate() {
            return document.getElementById('f-day').value;
        }

        // Every non-cancelled session, for any currently selected therapist, on
        // the reference date - refetched whenever that date changes. Booking a
        // recurring series still only checks this first occurrence up front;
        // the server skips (and reports) any later week that turns out to
        // conflict, same as before.
        let dayBookings = [];

        async function refreshDayBookings() {
            const date = timeGridReferenceDate();
            dayBookings = date ? (await api(`${FEED_URL}?start=${date}&end=${date}`)).sessions || [] : [];
            renderStartTimeGrid();
        }

        function busyTherapistNames(startMin, endMin) {
            const editingId = document.getElementById('f-id').value;
            const selectedTherapists = msTherapists.getSelected();

            const clashing = dayBookings.filter(s =>
                s.status !== 'cancelled' &&
                String(s.id) !== String(editingId) &&
                selectedTherapists.some(t => String(t.id) === String(s.therapist_id)) &&
                valueToMinutes(s.start_time) < endMin && valueToMinutes(s.end_time) > startMin
            );

            return [...new Set(clashing.map(s => (staffById(s.therapist_id) || {}).name).filter(Boolean))];
        }

        function renderStartTimeGrid() {
            const grid = document.getElementById('f-time-grid');
            const duration = parseInt(document.getElementById('f-duration').value, 10) || 60;
            let current = document.getElementById('f-start').value;
            grid.innerHTML = '';

            for (let mins = DAY_OPEN_MIN; mins + duration <= DAY_CLOSE_MIN; mins += duration) {
                const value = minutesToValue(mins);
                const busyWith = busyTherapistNames(mins, mins + duration);
                // A therapist/day change can turn the already-picked slot into a
                // conflict - drop the selection rather than silently keep it.
                if (value === current && busyWith.length) {
                    document.getElementById('f-start').value = '';
                    current = '';
                }
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'time-slot' + (value === current ? ' selected' : '') + (busyWith.length ? ' busy' : '');
                btn.textContent = minutesToLabel(mins);
                if (busyWith.length) {
                    btn.disabled = true;
                    btn.title = `${busyWith.join(', ')} already has a session then`;
                } else {
                    btn.addEventListener('click', () => {
                        document.getElementById('f-start').value = value;
                        renderStartTimeGrid();
                        closeTimeGrid();
                    });
                }
                grid.appendChild(btn);
            }
            if (!grid.children.length) {
                grid.innerHTML = '<div class="time-grid-empty">No slots long enough for this duration.</div>';
            }

            document.getElementById('f-time-trigger-label').textContent = current ? minutesToLabel(valueToMinutes(current)) : 'Select a time';
        }

        document.getElementById('f-time-trigger').addEventListener('click', () => {
            document.getElementById('f-time-grid').classList.toggle('open');
            document.getElementById('f-time-trigger').classList.toggle('open');
        });
        document.addEventListener('click', e => {
            if (!e.target.closest('#f-time-trigger') && !e.target.closest('#f-time-grid')) closeTimeGrid();
        });

        document.getElementById('f-duration').addEventListener('change', () => {
            // A longer duration may push the current selection past closing time -
            // drop it in that case so a stale, now-invalid time can't be submitted.
            const duration = parseInt(document.getElementById('f-duration').value, 10) || 60;
            const current = valueToMinutes(document.getElementById('f-start').value);
            if (current !== null && current + duration > DAY_CLOSE_MIN) {
                document.getElementById('f-start').value = '';
            }
            renderStartTimeGrid();
            updatePackageInfo();
        });
        document.getElementById('f-day').addEventListener('change', refreshDayBookings);

        function openPanel({ session = null, therapistId = null, date = null } = {}) {
            document.getElementById('panel-error').style.display = 'none';
            form.reset();
            closeTimeGrid();
            msTherapists.clear(); msPatients.clear(); msTypes.clear();
            occurrencesUserEdited = false;
            document.getElementById('pkg-info').style.display = 'none';
            document.getElementById('f-supervision-info').style.display = 'none';
            const wasCompleted = !!session && session.status === 'completed';
            document.getElementById('f-status-completed').hidden = !wasCompleted;
            document.getElementById('f-status-help').style.display = wasCompleted ? 'block' : 'none';
            document.getElementById('panel-delete').style.display = session && CAN_MANAGE ? '' : 'none';
            document.getElementById('f-repeats-wrap').style.display = session ? 'none' : '';

            if (session) {
                const readOnly = !CAN_MANAGE;
                document.getElementById('panel-title').textContent = readOnly ? 'Session details' : 'Edit session';
                document.getElementById('f-id').value = session.id;
                msTherapists.setSelected([session.therapist_id]);
                document.getElementById('f-day').value = session.session_date;
                document.getElementById('f-start').value = session.start_time;
                document.getElementById('f-duration').value = session.duration_minutes;
                if (session.patient_ids && session.patient_ids.length) msPatients.setSelected(session.patient_ids);
                else if (session.patient_id) msPatients.setSelected([session.patient_id]);
                if (session.is_custom_patient) {
                    msPatients.addItem({ id: `custom:${session.patient_name}`, name: session.patient_name, custom: true });
                    msPatients.setSelected([`custom:${session.patient_name}`]);
                }
                document.getElementById('f-activity').value = session.activity_label || '';
                (session.activity_types || [session.activity_type]).forEach(t => msTypes.addItem({ id: t, name: t }));
                msTypes.setSelected(session.activity_types || [session.activity_type]);
                document.getElementById('f-status').value = statusKey(session);
                document.getElementById('f-notice').value = session.cancel_notice_hours === null || session.cancel_notice_hours === undefined ? '' : Number(session.cancel_notice_hours);
                document.getElementById('f-notes').value = session.notes || '';
                if (session.supervised) {
                    document.getElementById('f-supervision-info').style.display = 'block';
                    document.getElementById('f-supervision-text').textContent = `${session.supervised_by_name || ''} · ${session.supervised_at || ''} — ${session.supervision_notes || ''}`;
                }
                setFormDisabled(readOnly);
                syncRepeatsUi(true);
            } else {
                document.getElementById('panel-title').textContent = 'Book session';
                document.getElementById('f-id').value = '';
                panelBaseDate = date || anchor;
                document.getElementById('f-day').value = panelBaseDate;
                msWeekdays.clear();
                document.getElementById('f-start').value = '09:00';
                if (therapistId) msTherapists.setSelected([therapistId]);
                setFormDisabled(false);
                syncRepeatsUi(false);
            }
            refreshDayBookings();
            // Pull fresh package/used-hours data every time the panel opens,
            // rather than trusting whatever LEADS held from page load -
            // updates the card in place once it lands (syncRepeatsUi() above
            // already rendered it once from whatever was on hand already).
            syncNoticeUi();
            refreshLeads().then(updatePackageInfo);
            panel.style.display = 'block';
        }
        function closePanel() { panel.style.display = 'none'; }
        document.getElementById('btn-book')?.addEventListener('click', () => openPanel());
        document.getElementById('panel-cancel').addEventListener('click', closePanel);
        panel.addEventListener('click', e => { if (e.target === panel) closePanel(); });

        form.addEventListener('submit', async e => {
            e.preventDefault();
            const id = document.getElementById('f-id').value;
            const patients = msPatients.getSelected();
            const customNames = patients.filter(p => p.custom).map(p => p.name);
            const status = document.getElementById('f-status').value;
            const payload = {
                patient_ids: patients.filter(p => !p.custom).map(p => p.id),
                custom_patient: customNames.length ? customNames.join(', ') : null,
                activity_label: document.getElementById('f-activity').value || null,
                activity_types: msTypes.getSelected().map(t => t.name),
                session_date: document.getElementById('f-day').value,
                start_time: document.getElementById('f-start').value,
                duration_minutes: document.getElementById('f-duration').value,
                notes: document.getElementById('f-notes').value,
            };
            // "Completed" is never sent - it's only ever set automatically. Leaving
            // it out keeps an auto-completed session completed on an unrelated edit.
            if (status !== 'completed') {
                payload.status = STATUS_WIRE[status] || status;
                // Only a family cancellation carries notice hours; anything
                // else clears whatever was recorded before.
                const notice = document.getElementById('f-notice').value;
                payload.cancel_notice_hours = NOTICE_STATES.includes(status)
                    ? (notice === '' ? defaultNotice(status) : Number(notice))
                    : null;
            }
            const therapists = msTherapists.getSelected().map(t => t.id);
            const errBox = document.getElementById('panel-error');
            errBox.style.display = 'none';

            if (!id && repeats.value === 'weekly' && !msWeekdays.getSelected().length) {
                errBox.textContent = 'Pick at least one day of the week.';
                errBox.style.display = 'block';
                return;
            }
            if (!msTypes.getSelected().length) {
                errBox.textContent = 'Pick a type.';
                errBox.style.display = 'block';
                return;
            }
            // With no explicit "Number of sessions", the series is auto-sized
            // from the patient's remaining package hours (packageSessionBudget()
            // on the server) - if there's nothing to size against, staff must
            // give an explicit count instead, so the series still always has a
            // defined stop. Checked here too so that's caught before a round
            // trip, not just by the server's own 422.
            if (!id && repeats.value === 'weekly' && !document.getElementById('f-occurrences').value) {
                const patientsSel = msPatients.getSelected();
                let hasBudget = false;
                if (patientsSel.length === 1 && !patientsSel[0].custom) {
                    const lead = LEADS.find(l => String(l.id) === String(patientsSel[0].id));
                    const type = msTypes.getSelected()[0].name;
                    hasBudget = ((lead && lead.packages) || []).some(p => (p.types || []).includes(type));
                }
                if (!hasBudget) {
                    errBox.textContent = 'Set a number of sessions — this patient has no package on file to size a repeating booking automatically.';
                    errBox.style.display = 'block';
                    return;
                }
            }
            if (!payload.start_time) {
                errBox.textContent = 'Pick a start time.';
                errBox.style.display = 'block';
                return;
            }

            try {
                let res;
                if (id) {
                    payload.therapist_id = therapists[0];
                    res = await api(`${BASE_URL}/${id}`, { method: 'PUT', body: JSON.stringify(payload) });
                } else {
                    payload.therapist_ids = therapists;
                    payload.repeats = repeats.value;
                    if (repeats.value === 'weekly') {
                        // payload.session_date already came from the (now
                        // always-visible) Date field above - it's the
                        // series' chosen start date, not just implicitly
                        // wherever the panel happened to be opened from.
                        payload.weekdays = msWeekdays.getSelected().map(d => d.id);
                        const occ = document.getElementById('f-occurrences').value;
                        if (occ) payload.occurrences = parseInt(occ, 10);
                    }
                    res = await api(STORE_URL, { method: 'POST', body: JSON.stringify(payload) });
                }
                closePanel();
                toast(res.message || 'Saved.');
                refresh();
                refreshLeads(); // so the next time this (or another) patient's panel opens, "used" already reflects what was just booked
            } catch (err) {
                errBox.textContent = Object.values(err.errors || {})[0]?.[0] || err.message;
                errBox.style.display = 'block';
            }
        });

        document.getElementById('panel-delete').addEventListener('click', async () => {
            const id = document.getElementById('f-id').value;
            if (!id || !confirm('Remove this session from the calendar?')) return;
            try {
                await api(`${BASE_URL}/${id}`, { method: 'DELETE' });
                closePanel(); toast('Session removed.'); refresh();
            } catch (e) { toast(e.message); }
        });

        // ---- Supervision ----
        const supBtn = document.getElementById('btn-supervision');
        if (supBtn) {
            supBtn.addEventListener('click', () => {
                supervisionMode = !supervisionMode;
                supBtn.textContent = supervisionMode ? 'Cancel supervision' : 'Log supervision';
                supBtn.classList.toggle('active', supervisionMode);
                refresh();
            });
        }
        const supModal = document.getElementById('sup-modal');
        let supSession = null;
        function openSupervision(s) {
            supSession = s;
            document.getElementById('sup-error').style.display = 'none';
            document.getElementById('sup-session-line').textContent = `${s.start_time} · ${s.patient_name} · ${s.therapist_name || ''} (${fmt(s.session_date, { weekday: 'short' })})`;
            document.getElementById('sup-observer-line').textContent = `Observed by ${CURRENT_USER_NAME}`;
            document.getElementById('sup-notes').value = s.supervision_notes || '';
            document.getElementById('sup-remove').style.display = s.supervised ? '' : 'none';
            supModal.classList.add('open');
            document.getElementById('sup-notes').focus();
        }
        document.getElementById('sup-cancel').addEventListener('click', () => supModal.classList.remove('open'));
        document.getElementById('sup-submit').addEventListener('click', async () => {
            const err = document.getElementById('sup-error');
            err.style.display = 'none';
            try {
                const res = await api(`${BASE_URL}/${supSession.id}/supervision`, { method: 'POST', body: JSON.stringify({ notes: document.getElementById('sup-notes').value }) });
                supModal.classList.remove('open');
                toast(res.message); refresh();
            } catch (e) { err.textContent = e.message; err.style.display = 'block'; }
        });
        document.getElementById('sup-remove').addEventListener('click', async () => {
            try {
                await api(`${BASE_URL}/${supSession.id}/supervision`, { method: 'DELETE' });
                supModal.classList.remove('open'); toast('Supervision removed.'); refresh();
            } catch (e) { toast(e.message); }
        });

        // ---- Mark leave ----
        const leaveBtn = document.getElementById('btn-leave');
        const leaveModal = document.getElementById('leave-modal');
        if (leaveBtn) {
            const lStaff = document.getElementById('l-staff');
            const lDay = document.getElementById('l-day');
            const lType = document.getElementById('l-type');
            lStaff.innerHTML = STAFF.map(t => `<option value="${t.id}">${escapeHtml(t.name)}</option>`).join('');
            lType.innerHTML = LEAVE_TYPES.map(t => `<option value="${escapeHtml(t)}">${escapeHtml(t)}</option>`).join('');

            async function updateImpact() {
                const box = document.getElementById('l-impact');
                box.className = 'impact-box';
                box.textContent = 'Checking booked sessions…';
                try {
                    const { count } = await api(`${LEAVE_IMPACT_URL}?user_id=${lStaff.value}&date=${lDay.value}`);
                    if (count) box.textContent = `${count} booked session${count === 1 ? '' : 's'} on that day will be cancelled — arrange cover.`;
                    else { box.classList.add('ok'); box.textContent = 'No booked sessions on that day.'; }
                } catch { box.textContent = 'Could not check booked sessions.'; }
            }
            leaveBtn.addEventListener('click', () => {
                const monday = mondayOf(anchor);
                lDay.innerHTML = '';
                for (let i = 0; i < 14; i++) {
                    const d = addDays(monday, i);
                    const iso = toIsoLocal(d);
                    const o = document.createElement('option');
                    o.value = iso;
                    o.textContent = `${DOW[i % 7]} ${d.getDate()}-${d.toLocaleDateString('en-GB', { month: 'short' })}`;
                    o.selected = iso === anchor;
                    lDay.appendChild(o);
                }
                document.getElementById('l-reason').value = '';
                document.getElementById('leave-error').style.display = 'none';
                leaveModal.classList.add('open');
                updateImpact();
            });
            lStaff.addEventListener('change', updateImpact);
            lDay.addEventListener('change', updateImpact);
            document.getElementById('l-cancel').addEventListener('click', () => leaveModal.classList.remove('open'));
            document.getElementById('l-submit').addEventListener('click', async () => {
                const err = document.getElementById('leave-error');
                err.style.display = 'none';
                try {
                    const res = await api(LEAVE_URL, { method: 'POST', body: JSON.stringify({
                        user_id: lStaff.value, leave_date: lDay.value, leave_type: lType.value,
                        reason: document.getElementById('l-reason').value,
                    }) });
                    leaveModal.classList.remove('open');
                    toast(res.message); refresh();
                } catch (e) {
                    err.textContent = Object.values(e.errors || {})[0]?.[0] || e.message;
                    err.style.display = 'block';
                }
            });
        }

        // ---- Utilisation report ----
        const utilModal = document.getElementById('util-modal');
        document.getElementById('btn-utilisation').addEventListener('click', async () => {
            const month = anchor.slice(0, 7);
            const table = document.getElementById('util-table');
            table.innerHTML = '<tr><td class="pos">Loading…</td></tr>';
            utilModal.classList.add('open');
            try {
                const r = await api(`${UTIL_URL}?month=${month}`);
                document.getElementById('util-sub').textContent = `Therapy hours delivered per week — ${r.month}`;
                table.innerHTML = `
                    <tr><th>Name</th><th>Position</th>${r.weeks.map(w => `<th class="num">${escapeHtml(w.label)}<small>${escapeHtml(w.range)}</small></th>`).join('')}<th class="num">Total</th></tr>
                    ${r.rows.map(row => `<tr><td>${escapeHtml(row.name)}</td><td class="pos">${escapeHtml(row.position)}</td>${row.hours.map(h => `<td class="num">${h.toFixed(1)}</td>`).join('')}<td class="num"><strong>${row.total.toFixed(1)}</strong></td></tr>`).join('')}
                    <tr class="total"><td>TOTAL</td><td></td>${r.totals.map(t => `<td class="num">${t.toFixed(1)}</td>`).join('')}<td class="num grand">${r.grand_total.toFixed(1)}</td></tr>`;
            } catch (e) {
                table.innerHTML = `<tr><td class="pos">${escapeHtml(e.message)}</td></tr>`;
            }
        });
        document.getElementById('util-close').addEventListener('click', () => utilModal.classList.remove('open'));
        [leaveModal, utilModal, supModal].forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); }));

        // Day view on a phone behaves like the leads pipeline: every therapist
        // shut to their header, one open at a time. The grid is rebuilt on every
        // date change, booking and refresh, so this listens on the container
        // rather than on columns that will not be there a moment later.
        (function () {
            const grid = document.getElementById('calendar-grid');
            if (!grid) return;

            const onNarrowScreen = () => window.matchMedia('(max-width: 820px)').matches;

            function setOpen(col, open) {
                col.classList.toggle('is-open', open);

                const body = col.querySelector('.col-body');
                if (!body) return;

                // Animating towards a guessed height would run the transition at
                // a different speed for every therapist, so the target is the
                // real content height - capped, past which the column scrolls
                // inside itself rather than pushing the next one off-screen.
                body.style.maxHeight = open
                    ? Math.min(body.scrollHeight, Math.round(window.innerHeight * 0.62)) + 'px'
                    : '';
            }

            grid.addEventListener('click', e => {
                if (!onNarrowScreen()) return;

                const header = e.target.closest('.therapist-col-header');
                if (!header) return;

                const col = header.closest('.therapist-col');
                const opening = !col.classList.contains('is-open');

                grid.querySelectorAll('.therapist-col').forEach(c => setOpen(c, false));
                if (opening) setOpen(col, true);
            });

            // The height above is written inline, so it would follow the columns
            // up to the wide layout and cap one that is no longer collapsible.
            window.addEventListener('resize', () => {
                if (onNarrowScreen()) return;

                grid.querySelectorAll('.therapist-col').forEach(c => {
                    c.classList.remove('is-open');
                    const body = c.querySelector('.col-body');
                    if (body) body.style.maxHeight = '';
                });
            });
        })();

        renderDay().then(() => {
            if (INITIAL_SESSION_ID && sessionsById[INITIAL_SESSION_ID]) {
                openPanel({ session: sessionsById[INITIAL_SESSION_ID] });
            }
        });
    })();
    </script>
@endsection
