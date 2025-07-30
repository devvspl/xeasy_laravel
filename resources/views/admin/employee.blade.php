@extends('layouts.app')
@section('content')
<div class="page-content">
    <div class="container-fluid">
        @section('title', ucwords(str_replace('-', ' ', Request::path())))
                    <x-theme.breadcrumb title="{{ ucwords(str_replace('-', ' ', Request::path())) }}" :breadcrumbs="[['label' => 'Master', 'url' => '#'], ['label' => ucwords(str_replace('-', ' ', Request::path()))]]" />
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0 flex-grow-1"><i class="ri-list-unordered"></i> Employee Status</h4>
                                    <div class="flex-shrink-0">

                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table style="margin-top: 15px" id="claimReportTable"
                                            class="table nowrap dt-responsive align-middle table-hover table-bordered"
                                            style="width:100%">
                                            <thead class="table-light">
                                                <tr>
                                                    <th rowspan="2">Claim Month</th>
                                                    <th rowspan="2">Total Claim</th>
                                                    <th>Filling</th>
                                                    <th>Claimant Approval</th>
                                                    <th>Verify Approval</th>
                                                    <th>Reporting Approval</th>
                                                    <th>Finance Approval</th>
                                                    {{-- <th colspan="5">Pending For</th> --}}
                                                    <th rowspan="2">View Home</th>
                                                    <th rowspan="2">Return Claim</th>
                                                    <th rowspan="2">Allow Submit</th>
                                                    <th rowspan="2">Backend Submission</th>
                                                    <th rowspan="2">Details</th>
                                                </tr>
                                                <tr>

                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $claims = [
                                                        ['month' => 'Draft', 'total' => '', 'claimant' => '', 'verify' => '', 'reporting' => '', 'finance' => '', 'view' => true, 'return' => false, 'submit' => false],
                                                        ['month' => 'Apr', 'total' => 22, 'claimant' => 1, 'verify' => '', 'reporting' => '', 'finance' => '', 'view' => true, 'return' => true, 'submit' => true],
                                                        ['month' => 'May', 'total' => 21, 'claimant' => '', 'verify' => '', 'reporting' => '', 'finance' => '', 'view' => true, 'return' => true, 'submit' => true],
                                                        ['month' => 'Jun', 'total' => 16, 'claimant' => '', 'verify' => '', 'reporting' => '', 'finance' => '', 'view' => true, 'return' => true, 'submit' => true],
                                                        ['month' => 'Jul', 'total' => 13, 'claimant' => 1, 'verify' => 12, 'reporting' => '', 'finance' => '', 'view' => true, 'return' => true, 'submit' => true],
                                                    ];
                                                   @endphp
                                                @foreach ($claims as $claim)
                                                    <tr>
                                                        <td>{{ $claim['month'] }}</td>
                                                        <td>{{ $claim['total'] }}</td>
                                                        <td></td>
                                                        <td>{!! $claim['claimant'] ? "<a href='#'><b>{$claim['claimant']}</b></a>" : '' !!}
                                                        </td>
                                                        <td>{!! $claim['verify'] ? "<a href='#'><b>{$claim['verify']}</b></a>" : '' !!}
                                                        </td>
                                                        <td>{{ $claim['reporting'] }}</td>
                                                        <td>{{ $claim['finance'] }}</td>
                                                        <td>
                                                            @if($claim['view'])
                                                                <button class="btn btn-success btn-sm">E-Home</button>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($claim['return'])
                                                                <button class="btn btn-warning btn-sm">Return</button>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($claim['submit'])
                                                                <input type="checkbox" />
                                                            @endif
                                                        </td>
                                                        <td></td>
                                                        <td><a href="#" class="btn btn-info btn-sm">View</a></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="mt-3 fw-bold">Team : <a href="#"><b>1263</b>-Saurabh Nishad</a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endsection
@push('scripts')
@endpush