@extends('layouts.app')


@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-default">
                    <div class="card-header">{{ $title }}</div>

                    <div class="card-body">
                        {{ $content }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
