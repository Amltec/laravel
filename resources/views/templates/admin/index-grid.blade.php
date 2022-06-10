@extends('templates.admin.index',[
    'dashboard'=>array_merge([
        'grid_page'=>true
    ],($dashboard??[]))
])