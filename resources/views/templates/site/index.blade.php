<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>@yield('title', 'Home') | {{config('app.name')}}</title>

<meta name="viewport" content="width=divice-width, initial-scale=1.0" />

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css" integrity="sha384-Smlep5jCw/wG7hdkwQ/Z5nLIefveQRIY9nfy6xoR1uRYBtpZgI6339F5dgvm/e9B" crossorigin="anonymous">

<script src="https://code.jquery.com/jquery-3.3.1.min.js" ></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js" integrity="sha384-o+RDsa0aLu++PJvFqy8fFScvbHFLtbvScb8AjopnFD+iEQ7wo/CG0xlczd+2O/em" crossorigin="anonymous"></script>

<script src="{{asset('js/site.js?ver=1.24')}}"></script>
<link href="{{asset('css/site.css?ver=1.24')}}" rel="stylesheet" type="text/css">

{!! Form::writeScripts() !!}
@stack('head')

<script>
var isLocalhost={{config('app.env')=='local'?'true':'false'}};
var isMobile={{isMobile()?'true':'false'}};
var site_vars={
    @if(isset($account))login:'{{$account->login}}',@endif
    token:'{{csrf_token()}}',
    url:'{{URL::to("/")}}',
    url_current:'{{URL::current()}}'
};
</script>

</head>
<body class="{{isMobile() ? 'body-mobile' : 'body-desktop'}}">
@if(isset($account) && (!empty($account->cover_image) || !empty($account->cover_image_mob)) )
<div class="slider">
    <img src="{{$account->getStorageUrl(isMobile() ? $account->cover_image_mob : $account->cover_image)}}" class="slider-image" >
</div>
@endif
<div class="lay">
<div class='content-view' id="content-view">@yield('content-view')</div>
@stack('bottom')
</div>
</body>
</html>
