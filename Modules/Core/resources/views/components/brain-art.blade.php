@php($uid = 'ba'.substr(md5(uniqid('', true)), 0, 6))
<svg {{ $attributes->class(['brain-art']) }} viewBox="0 0 320 320" role="img" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <radialGradient id="{{ $uid }}-glow" cx="50%" cy="48%" r="50%"><stop offset="0" class="ba-s-glow"/><stop offset="1" class="ba-s-glow ba-s-clear"/></radialGradient>
        <linearGradient id="{{ $uid }}-l" x1="0" y1="0" x2="1" y2="1"><stop offset="0" class="ba-s-green"/><stop offset=".55" class="ba-s-lime"/><stop offset="1" class="ba-s-yellow"/></linearGradient>
        <linearGradient id="{{ $uid }}-r" x1="1" y1="0" x2="0" y2="1"><stop offset="0" class="ba-s-navy"/><stop offset=".6" class="ba-s-violet"/><stop offset="1" class="ba-s-red"/></linearGradient>
        <path id="{{ $uid }}-h" d="M158 62C140 48 108 54 96 78 76 82 58 104 66 130 50 144 52 176 72 188 70 210 88 232 112 232 128 246 150 242 158 232Z"/>
        <g id="{{ $uid }}-f"><path d="M96 78C112 84 120 96 118 110M66 130C86 128 100 136 104 150M72 188C90 182 104 170 102 156M112 232C112 214 124 204 138 202M118 110C130 112 140 120 142 134M104 150C120 150 132 158 134 172M138 202C146 190 144 178 134 172M142 134C150 128 154 118 152 104"/></g>
    </defs>
    <circle class="ba-glow" cx="160" cy="150" r="130" fill="url(#{{ $uid }}-glow)"/>
    <g class="ba-ring"><circle cx="160" cy="150" r="146" fill="none" class="ba-dash"/><circle class="ba-orb ba-orb-1" cx="160" cy="4" r="5.500"/><circle class="ba-orb ba-orb-2" cx="286.400" cy="223" r="4.500"/><circle class="ba-orb ba-orb-3" cx="33.600" cy="223" r="4"/></g>
    <g class="ba-body">
        <path d="M150 238c2 16 18 16 20 0" class="ba-stem"/>
        <use href="#{{ $uid }}-h" class="ba-hemi" fill="url(#{{ $uid }}-l)"/>
        <use href="#{{ $uid }}-h" class="ba-hemi ba-hemi-r" fill="url(#{{ $uid }}-r)" transform="matrix(-1 0 0 1 320 0)"/>
        <g class="ba-fold"><use href="#{{ $uid }}-f"/><use href="#{{ $uid }}-f" transform="matrix(-1 0 0 1 320 0)"/></g>
        <g class="ba-syn" fill="none">
            <path pathLength="100" d="M84 112Q100 100 118 110T142 134"/><path pathLength="100" d="M104 150Q120 168 134 172"/><path pathLength="100" d="M142 134Q160 120 178 134"/><path pathLength="100" d="M178 134Q196 120 202 110"/><path pathLength="100" d="M134 172Q160 190 186 172"/><path pathLength="100" d="M138 202Q160 214 182 202"/>
        </g>
        <g class="ba-sparks" fill="none">
            @foreach ([0, 1, 2, 3, 4, 5] as $i)
                <path class="ba-run" style="--i: {{ $i }}" pathLength="100" d="{{ ['M84 112Q100 100 118 110T142 134', 'M104 150Q120 168 134 172', 'M142 134Q160 120 178 134', 'M178 134Q196 120 202 110', 'M134 172Q160 190 186 172', 'M138 202Q160 214 182 202'][$i] }}"/>
            @endforeach
        </g>
        @foreach ([[118, 110], [142, 134], [104, 150], [134, 172], [138, 202], [84, 112], [202, 110], [178, 134], [216, 150], [186, 172], [182, 202], [236, 112]] as $i => [$x, $y])
            <circle class="ba-n" style="--i: {{ $i }}" cx="{{ $x }}" cy="{{ $y }}" r="4"/>
        @endforeach
    </g>
</svg>
