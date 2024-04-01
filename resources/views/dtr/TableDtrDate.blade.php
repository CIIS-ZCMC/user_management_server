<td class="time " id="entry{{ $i }}1">
    <!--FIRST IN -->


    @include('dtr.DtrSeparator', ['entry' => 'firstin', 'schedule' => $schedule])
</td>
<td class="time " id="entry{{ $i }}2">

    @include('dtr.DtrSeparator', ['entry' => 'firstout', 'fspan' => false])
</td>
<td class="time " id="entry{{ $i }}3">

    {{-- @if (!$halfsched)
        @include('dtr.DtrSeparator',['entry'=>'secondin'])
        @endif --}}
    @include('dtr.DtrSeparator', ['entry' => 'secondin'])
</td>
<td class="time " id="entry{{ $i }}4">

    {{-- @if (!$halfsched)
        @include('dtr.DtrSeparator',['entry'=>'secondout'])
        @endif --}}

    @include('dtr.DtrSeparator', ['entry' => 'secondout'])
</td>
<td style="width: 40px !important; padding:10px;border-right :1px solid rgb(196, 197, 201);border-left :1px solid rgb(196, 197, 201)"
    class="time">
    @include('dtr.DtrSeparator', ['entry' => 'undertime'])
</td>
<td style="background-color: whitesmoke;width: 80px !important;border-right: 1px solid rgb(184, 184, 184)"
    id="wsched{{ $i }}">
    @include('dtr.DtrSeparator', ['entry' => 'wsched'])

</td>

<td style="background-color: whitesmoke;width: 200px !important;">
    @include('dtr.DtrSeparator', ['entry' => 'remarks'])

</td>
