@extends('errors.layout')

@section('title', 'Halaman Tidak Ditemukan')
@section('code', 'KESALAHAN 404')
@section('heading', 'Halaman tidak ditemukan')
@section('message', 'Alamat mungkin sudah berubah, data telah dihapus, atau QR yang dipindai tidak terdaftar di GearTrack.')
@section('secondaryUrl', route('qr.scan'))
@section('secondaryLabel', 'Scan QR Lain')
