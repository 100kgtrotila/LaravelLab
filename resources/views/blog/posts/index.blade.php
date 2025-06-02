@extends('layouts.main')

@section('content')
    <table class="table table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>Назва</th>
            <th>Дата створення</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($items as $item)
            <tr>
                <td>{{$item->id}}</td>
                <td>{{$item->title}}</td>
                <td>{{$item->created_at}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
