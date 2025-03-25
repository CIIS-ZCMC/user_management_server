                     
    <td>
        <!--FIRST IN -->
        @include('generate_dtr.dtr_separator',['entry'=>'firstin'])
    </td>
    <td>
        @include('generate_dtr.dtr_separator',['entry'=>'firstout' ,'fspan'=> false])
    </td>
    <td>
        @if (!$halfsched)
        @include('generate_dtr.dtr_separator',['entry'=>'secondin'])
        @endif
       
    </td>
    <td>
        @if (!$halfsched)
        @include('generate_dtr.dtr_separator',['entry'=>'secondout'])
        @endif
    </td>
    <td style="width: 30px !important;">
        @include('generate_dtr.dtr_separator',['entry'=>'undertime'])
    </td>

 