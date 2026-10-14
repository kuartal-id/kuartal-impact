@props(['name' => 'mangrove', 'grow' => false])
{{-- Hand-drawn nature line work, one per fund (mangrove roots, coral, sun + wind, botanical) plus
     'roots' (generic branching, page heroes). Stroke only, coloured by theme (.k-motif in app.css).
     grow=true draws the lines in once; reduced motion shows them complete. --}}
@php
    $vb = '0 0 400 300';
@endphp
<svg viewBox="{{ $vb }}" aria-hidden="true" focusable="false" {{ $attributes->merge(['class' => 'k-motif'.($grow ? ' is-growing' : '')]) }}>
@switch($name)
@case('mangrove')
    {{-- Mangrove: lobed canopy, trunk, prop roots arching through the tide line into the mud. --}}
    <g stroke-width="1.4">
        <path class="k-m-grow k-m-hi" d="M196 150C198 116 194 80 200 40"/>
        <path class="k-m-grow" style="--d:.3s" d="M198 72C214 60 232 54 252 52M197 86C180 74 166 68 148 66"/>
        <path class="k-m-grow" style="--d:.4s" d="M96 74C96 58 116 48 132 54C138 38 162 32 176 42C186 28 214 28 224 42C240 32 266 38 270 54C288 50 306 62 302 78C298 92 280 96 268 90C256 102 232 102 222 92C206 102 186 102 176 92C160 102 136 102 128 90C112 96 96 90 96 74Z"/>
        <path class="k-m-soft" d="M120 72C140 66 160 70 176 64M214 62C232 70 256 66 280 72"/>
    </g>
    <g stroke-width="1.6">
        <path class="k-m-grow" style="--d:.5s" d="M197 112C176 118 150 140 138 204C134 226 132 244 130 258"/>
        <path class="k-m-grow" style="--d:.6s" d="M196 124C170 128 122 150 102 204C94 224 90 240 88 252"/>
        <path class="k-m-grow" style="--d:.7s" d="M197 136C188 160 180 190 178 266"/>
        <path class="k-m-grow" style="--d:.55s" d="M198 110C224 118 250 144 262 204C266 226 268 246 272 262"/>
        <path class="k-m-grow" style="--d:.65s" d="M198 124C232 132 288 158 306 204C312 222 314 236 318 248"/>
        <path class="k-m-grow" style="--d:.75s" d="M199 140C212 166 222 200 228 258"/>
        <path class="k-m-grow" style="--d:.8s" d="M196 150C150 154 84 178 60 240"/>
        <path class="k-m-grow" style="--d:.85s" d="M199 150C252 156 320 178 346 236"/>
    </g>
    <path class="k-m-dash k-m-hi" stroke-width="1.2" d="M16 204H384"/>
    <path class="k-m-soft" stroke-width="1" d="M40 216C90 212 120 220 170 216S270 212 360 218M60 230C110 226 160 234 220 230S320 228 350 232"/>
    <path stroke-width="1.2" d="M40 204V192M52 204V196M366 204V190M354 204V195"/>
    <path class="k-m-soft" stroke-width="1" d="M24 268H376"/>
@break
@case('coral')
    {{-- Coral: a branching colony on the seabed, polyp tips as dots. --}}
    <g stroke-width="1.6">
        <path class="k-m-grow" d="M200 280C200 242 196 220 190 190C184 160 170 140 150 120"/>
        <path class="k-m-grow" style="--d:.4s" d="M190 190C202 170 214 160 222 130C228 106 226 90 232 70"/>
        <path class="k-m-grow" style="--d:.8s" d="M150 120C140 100 128 92 120 70"/>
        <path class="k-m-grow" style="--d:.85s" d="M150 120C162 100 160 85 166 62"/>
        <path class="k-m-grow" style="--d:.9s" d="M222 130C240 118 256 110 262 88"/>
        <path class="k-m-grow" style="--d:.3s" d="M200 250C230 240 260 226 280 200C296 180 300 160 316 140"/>
        <path class="k-m-grow" style="--d:.9s" d="M280 200C292 196 316 192 330 176"/>
        <path class="k-m-grow" style="--d:1s" d="M316 140C312 120 318 104 326 90"/>
        <path class="k-m-grow" style="--d:.35s" d="M200 252C170 244 136 232 116 210C100 192 96 170 82 150"/>
        <path class="k-m-grow" style="--d:.95s" d="M116 210C100 210 80 206 66 194"/>
        <path class="k-m-grow" style="--d:1s" d="M82 150C86 130 80 116 72 100"/>
    </g>
    <g class="k-m-hi">
        @foreach ([[120,70],[166,62],[232,70],[262,88],[326,90],[330,176],[66,194],[72,100]] as [$cx, $cy])
            <circle class="k-m-dot" style="--d:{{ $loop->index * .08 }}s" cx="{{ $cx }}" cy="{{ $cy }}" r="3.2"/>
        @endforeach
    </g>
    <path stroke-width="1.2" d="M24 280H376"/>
    <path class="k-m-dash" stroke-width="1.2" d="M40 290H360"/>
    <path class="k-m-soft" stroke-width="1" d="M30 30C80 24 120 36 170 30S260 22 370 32M30 46C90 40 150 52 210 46S320 40 370 48"/>
@break
@case('energy')
    {{-- Re-Energize: sun with measured rays, a turbine, wind lines over the land contour. --}}
    <g stroke-width="1.4">
        <circle class="k-m-hi" cx="118" cy="104" r="30"/>
        <circle class="k-m-dash" cx="118" cy="104" r="46"/>
        @for ($i = 0; $i < 16; $i++)
            @php $a = deg2rad($i * 22.5); $r1 = $i % 2 ? 56 : 54; $r2 = $i % 2 ? 64 : 72; @endphp
            <path d="M{{ round(118 + $r1 * cos($a), 1) }} {{ round(104 + $r1 * sin($a), 1) }}L{{ round(118 + $r2 * cos($a), 1) }} {{ round(104 + $r2 * sin($a), 1) }}"/>
        @endfor
    </g>
    <g stroke-width="1.6">
        <path d="M292 262L297 122L302 262"/>
        <circle cx="297" cy="118" r="4.5"/>
        <g class="k-m-hi">
            <path d="M297 118C302 92 302 70 296 46"/>
            <path d="M297 118C302 92 302 70 296 46" transform="rotate(120 297 118)"/>
            <path d="M297 118C302 92 302 70 296 46" transform="rotate(240 297 118)"/>
        </g>
    </g>
    <path class="k-m-grow" stroke-width="1.2" d="M16 200C80 186 140 214 210 198S330 182 384 192"/>
    <path class="k-m-grow k-m-soft" style="--d:.3s" stroke-width="1.2" d="M16 222C90 210 150 236 220 222S340 206 384 214"/>
    <path stroke-width="1.2" d="M16 262C70 252 120 258 170 250S280 256 384 248"/>
    <path class="k-m-soft" stroke-width="1" d="M16 276C80 270 140 276 200 270S320 274 384 268"/>
@break
@case('healing')
    {{-- Healing: a botanical leaf with venation and a young sprig. --}}
    <path class="k-m-grow" stroke-width="1.6" d="M200 264C128 228 98 150 140 70C160 38 186 24 200 20C214 24 240 38 260 70C302 150 272 228 200 264Z"/>
    <path class="k-m-grow k-m-hi" style="--d:.2s" stroke-width="1.6" d="M200 290L200 30"/>
    <g stroke-width="1.1">
        @foreach ([[70, 40], [100, 58], [130, 66], [160, 66], [190, 58], [220, 42]] as [$y, $wd])
            <path class="k-m-grow" style="--d:{{ .5 + $loop->index * .12 }}s" d="M200 {{ $y + 18 }}C{{ 200 - $wd * .4 }} {{ $y + 10 }} {{ 200 - $wd * .8 }} {{ $y - 4 }} {{ 200 - $wd }} {{ $y - 16 }}"/>
            <path class="k-m-grow" style="--d:{{ .55 + $loop->index * .12 }}s" d="M200 {{ $y + 18 }}C{{ 200 + $wd * .4 }} {{ $y + 10 }} {{ 200 + $wd * .8 }} {{ $y - 4 }} {{ 200 + $wd }} {{ $y - 16 }}"/>
            <circle class="k-m-dot k-m-hi" cx="{{ 200 - $wd }}" cy="{{ $y - 16 }}" r="2"/>
            <circle class="k-m-dot k-m-hi" cx="{{ 200 + $wd }}" cy="{{ $y - 16 }}" r="2"/>
        @endforeach
    </g>
    <path class="k-m-soft" stroke-width="1.2" d="M200 250C232 252 262 244 288 226M288 226C300 210 318 204 336 206C330 222 312 232 288 226ZM312 214L288 226"/>
    <path class="k-m-soft" stroke-width="1.2" d="M200 236C170 238 140 230 116 214M116 214C104 200 88 196 72 198C78 212 94 220 116 214ZM94 205L116 214"/>
    <path stroke-width="1" class="k-m-dash" d="M120 290H280"/>
@break
@default
    {{-- Roots: generic branching growth line used on page heroes. --}}
    <g stroke-width="1.4">
        <path class="k-m-grow k-m-hi" d="M0 210C60 206 110 214 150 190C186 168 206 130 246 116C290 100 340 110 400 96"/>
        <path class="k-m-grow" style="--d:.4s" d="M150 190C160 220 190 240 230 248C262 254 290 270 310 300"/>
        <path class="k-m-grow" style="--d:.6s" d="M246 116C250 90 270 70 300 60C324 52 340 36 350 14"/>
        <path class="k-m-grow" style="--d:.8s" d="M230 248C240 232 262 226 286 228"/>
        <path class="k-m-grow" style="--d:.85s" d="M300 60C320 66 344 64 366 54"/>
        <path class="k-m-grow" style="--d:.9s" d="M90 208C96 230 92 252 80 272"/>
        <path class="k-m-grow k-m-soft" style="--d:1s" d="M340 104C350 124 370 136 396 140"/>
    </g>
    <g class="k-m-hi">
        <circle class="k-m-dot" cx="350" cy="14" r="2.6"/><circle class="k-m-dot" cx="366" cy="54" r="2.6"/>
        <circle class="k-m-dot" cx="286" cy="228" r="2.6"/><circle class="k-m-dot" cx="80" cy="272" r="2.6"/>
    </g>
@endswitch
</svg>
