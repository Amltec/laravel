@php
    $name_cid = explode('|',$name)[0];
    $name_uf = explode('|',$name)[1];
@endphp

<div class="form-group form-group-type-cidade-uf form-group-{{$name}} {{$class_group ?? ''}}" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{{$label}}</label>
    @endif
    
    <div class="control-div {{$class_div ?? ''}}">
      <span class="form-group-fields-col-a1">
          <input type="text" class="form-control" {!! !empty($id) ? 'id="'.explode('|',$id)[0].'"':'' !!} name='{{$name_cid}}' placeholder="{{ isset($placeholder) && $placeholder===true ? 'Cidade' : '' }}" maxlength="{{$maxlength ?? ''}}" value="{{ data_get($autodata,$name_cid) ?? Form::getValueAttribute($name_cid) ?? $value ?? '' }}" data-type="cidade" autocomplete="no" data-name="cidade" >
          <span class="help-block"></span>
      </span>
      <span class="form-group-fields-col-a2">
      @php
      $list = [ '_blank_'=>'',
                'AC'=>'AC',
                'AL'=>'AL',
                'AM'=>'AM',
                'AP'=>'AP',
                'BA'=>'BA',
                'CE'=>'CE',
                'DF'=>'DF',
                'ES'=>'ES',
                'GO'=>'GO',
                'MA'=>'MA',
                'MG'=>'MG',
                'MS'=>'MS',
                'MT'=>'MT',
                'PA'=>'PA',
                'PB'=>'PB',
                'PE'=>'PE',
                'PI'=>'PI',
                'PR'=>'PR',
                'RJ'=>'RJ',
                'RN'=>'RN',
                'RO'=>'RO',
                'RR'=>'RR',
                'RS'=>'RS',
                'SC'=>'SC',
                'SE'=>'SE',
                'SP'=>'SP',
                'TO'=>'TO',
            ];
            
      if(isset($placeholder) && $placeholder===true)$list=['_first_value_'=>'UF'] + $list;
      $tmp = Form::select($name_uf, 
            $list, 
            strtoupper(data_get($autodata,$name_uf) ?? Form::getValueAttribute($name_uf) ?? $value_uf ?? ($type=='uf'?$value:'') )  ,
            array_filter([
                'id'=>(isset($id) ? explode('|',$id)[1]??null : ''),
                'class'=>'form-control',
                'data-type'=>'uf',
                'data-name'=>'uf'
            ])
      );
      
      echo str_replace(['value="_first_value_"','value="_blank_"'], ['value="_first_value_" disabled'. (isset($placeholder) && $placeholder===true?' selected':'') ,'value="_blank_"'], $tmp);
      
      @endphp
          <span class="help-block"></span>
      </span>
    </div>
</div>

