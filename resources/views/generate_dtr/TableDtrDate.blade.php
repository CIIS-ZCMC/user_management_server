<style>
     .space{
        width: 500px !important;
        height:30px !important;
        font-size: 9px !important;
    }
</style>
<td class="space" style="width: 50px !important">
    <!--FIRST IN -->
    @include('generate_dtr.DtrSeparator',['entry'=>'firstin','schedule'=>$schedule])
</td>
<td class="space" style="width: 50px !important">
    @include('generate_dtr.DtrSeparator',['entry'=>'firstout' ,'fspan'=> false])
</td>
<td class="space" style="width: 50px !important">
    {{-- @if (!$halfsched)
    @include('generate_dtr.DtrSeparator',['entry'=>'secondin'])
    @endif --}}
    @include('generate_dtr.DtrSeparator',['entry'=>'secondin'])
</td>
<td class="space" style="width: 50px !important">
    {{-- @if (!$halfsched)
    @include('generate_dtr.DtrSeparator',['entry'=>'secondout'])
    @endif --}}
    @include('generate_dtr.DtrSeparator',['entry'=>'secondout'])
</td>
<td style="width: 40px !important;font-size:10px;height:40px;">
    @include('generate_dtr.DtrSeparator',['entry'=>'undertime_hours'])
</td>
<td style="width: 40px !important;font-size:10px;">
    @include('generate_dtr.DtrSeparator',['entry'=>'undertime_minutes'])
</td>

