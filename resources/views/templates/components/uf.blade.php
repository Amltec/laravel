<div class="form-group form-group-type-uf form-group-{{$name}} {{$class_group ?? ''}}" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{{$label}}</label>
    @endif
    
    <div class="control-div {{$class_div ?? ''}}">
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
            
      $tmp = Form::select($name, 
            $list, 
            data_get($autodata,$name) ?? $value ?? Form::getValueAttribute($name) ?? null,
            array_filter([
                'id'=>(isset($id) ? $id??null : ''),
                'class'=>'form-control',
                'data-type'=>'uf',
                'data-name'=>'uf'
            ])
      );
      
      echo str_replace(['value="_first_value_"','value="_blank_"'], ['value="_first_value_" disabled'. (isset($placeholder) && $placeholder===true?' selected':'') ,'value="_blank_"'], $tmp);
      
      @endphp
      <span class="help-block"></span>
    </div>
</div>

