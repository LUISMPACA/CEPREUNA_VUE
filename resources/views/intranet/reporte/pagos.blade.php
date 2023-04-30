@extends('layouts.app')
@section('titulo', 'Reporte | Pagos')

@section('content')

    <i-reporte-pagos :permissions="{{ $permisos }}"></i-reporte-pagos>


@endsection

@section('scripts')

@endsection
