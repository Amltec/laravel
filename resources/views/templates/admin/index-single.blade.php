@extends('templates.admin.index',[
    'dashboard'=>array_merge([
        'single_page'=>true
    ],($dashboard??[]))
])