@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-default">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif


                    <h3>@lang('home.home_devices')</h3>

                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Device</th>
                            <th>Date Recorded</th>
                        </tr>
                        </thead>
                        <tbody>
                    @foreach($devices as $device)
                        <tr>
                            <td>{{ $device['device'] }}</td>
                            <td>{{ date('M d, Y - H:i:s', strtotime($device['created_at'])) }}</td>
                        </tr>
                    @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
