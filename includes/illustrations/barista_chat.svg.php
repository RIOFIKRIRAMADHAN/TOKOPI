<?php // Ilustrasi orisinal: pelanggan mengobrol dengan barista di bar. Warna mengikuti palet publik. ?>
<svg class="ill-barista" viewBox="0 0 400 440" role="img" aria-labelledby="illTitle illDesc" xmlns="http://www.w3.org/2000/svg">
    <title id="illTitle">Pelanggan mengobrol dengan barista</title>
    <desc id="illDesc">Ilustrasi barista di balik bar menyajikan secangkir kopi hangat sambil berbincang dengan pelanggan.</desc>

    <!-- latar -->
    <circle cx="200" cy="230" r="178" fill="#EFE7D7"/>
    <g opacity=".55">
        <circle cx="70" cy="120" r="2.5" fill="#D9C9A9"/><circle cx="330" cy="150" r="2.5" fill="#D9C9A9"/>
        <circle cx="355" cy="250" r="2" fill="#D9C9A9"/><circle cx="48" cy="260" r="2" fill="#D9C9A9"/>
    </g>

    <!-- lampu gantung -->
    <line x1="200" y1="0" x2="200" y2="58" stroke="#2D1B0A" stroke-width="2"/>
    <circle class="ill-glow" cx="200" cy="92" r="46" fill="#F3C98B" opacity=".28"/>
    <path d="M178 80 Q200 54 222 80 Z" fill="#2D1B0A"/>
    <circle cx="200" cy="82" r="5" fill="#F3C98B"/>

    <!-- rak & toples di belakang barista -->
    <rect x="44" y="170" width="98" height="5" rx="2.5" fill="#B9A88F"/>
    <rect x="54" y="146" width="18" height="24" rx="4" fill="#D9B47A"/>
    <rect x="78" y="138" width="20" height="32" rx="4" fill="#A9471F" opacity=".85"/>
    <rect x="104" y="150" width="16" height="20" rx="4" fill="#56622F" opacity=".85"/>

    <!-- BARISTA -->
    <g class="ill-barista-fig">
        <!-- kemeja -->
        <rect x="74" y="222" width="96" height="110" rx="44" fill="#3B2412"/>
        <!-- apron dengan tali leher & saku -->
        <path d="M96 246 h52 l6 86 h-64 z" fill="#A9471F"/>
        <path d="M104 246 L114 222 M140 246 L130 222" stroke="#A9471F" stroke-width="4" stroke-linecap="round"/>
        <rect x="108" y="276" width="28" height="18" rx="4" fill="none" stroke="#7A2F12" stroke-width="2.5"/>
        <rect x="114" y="204" width="16" height="22" rx="6" fill="#C98B62"/>
        <g class="ill-head-a">
            <circle cx="122" cy="186" r="30" fill="#C98B62"/>
            <path d="M92 182 Q94 150 124 152 Q150 152 152 176 Q140 166 122 168 Q104 170 92 182 Z" fill="#2D1B0A"/>
            <circle cx="102" cy="160" r="9" fill="#2D1B0A"/>
            <circle cx="136" cy="188" r="3" fill="#2D1B0A"/>
            <path d="M132 199 q6 4 12 -1" fill="none" stroke="#2D1B0A" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="142" cy="194" r="4" fill="#E8A27F" opacity=".7"/>
        </g>
        <!-- lengan menyajikan cangkir -->
        <path d="M152 246 Q178 266 186 298" fill="none" stroke="#3B2412" stroke-width="18" stroke-linecap="round"/>
        <circle cx="188" cy="300" r="9" fill="#C98B62"/>
    </g>

    <!-- PELANGGAN -->
    <g class="ill-guest-fig">
        <rect x="236" y="226" width="98" height="106" rx="44" fill="#56622F"/>
        <path d="M262 232 l23 24 l23 -24" fill="none" stroke="#3F4A20" stroke-width="3" stroke-linejoin="round"/>
        <rect x="276" y="206" width="16" height="22" rx="6" fill="#8D5A3B"/>
        <g class="ill-head-b">
            <circle cx="284" cy="188" r="30" fill="#8D5A3B"/>
            <g fill="#2D1B0A">
                <circle cx="300" cy="166" r="13"/><circle cx="282" cy="160" r="13"/><circle cx="266" cy="168" r="11"/>
                <circle cx="312" cy="182" r="11"/><circle cx="310" cy="200" r="9"/>
            </g>
            <circle cx="268" cy="190" r="3" fill="#2D1B0A"/>
            <path d="M258 202 q6 5 12 0" fill="none" stroke="#2D1B0A" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="262" cy="196" r="4" fill="#C98B62" opacity=".7"/>
        </g>
        <!-- lengan bertumpu di meja bar -->
        <path d="M250 256 Q232 280 236 300" fill="none" stroke="#56622F" stroke-width="18" stroke-linecap="round"/>
        <circle cx="238" cy="302" r="9" fill="#8D5A3B"/>
    </g>

    <!-- meja bar -->
    <rect x="28" y="304" width="344" height="20" rx="6" fill="#8A5A2B"/>
    <rect x="40" y="324" width="320" height="96" rx="4" fill="#2D1B0A"/>
    <g stroke="#F6F1E7" stroke-opacity=".08" stroke-width="2">
        <line x1="80" y1="332" x2="80" y2="412"/><line x1="120" y1="332" x2="120" y2="412"/><line x1="160" y1="332" x2="160" y2="412"/>
        <line x1="200" y1="332" x2="200" y2="412"/><line x1="240" y1="332" x2="240" y2="412"/><line x1="280" y1="332" x2="280" y2="412"/><line x1="320" y1="332" x2="320" y2="412"/>
    </g>

    <!-- cangkir + uap -->
    <ellipse cx="210" cy="304" rx="22" ry="5" fill="#F6F1E7"/>
    <path d="M194 282 h32 v10 q0 12 -16 12 q-16 0 -16 -12 z" fill="#F6F1E7"/>
    <path d="M226 286 q10 0 8 8 q-2 6 -9 4" fill="none" stroke="#F6F1E7" stroke-width="4"/>
    <ellipse cx="210" cy="283" rx="15" ry="3" fill="#6B3E1E"/>
    <g class="ill-steam" fill="none" stroke="#B9A88F" stroke-width="3" stroke-linecap="round">
        <path d="M202 272 q-6 -8 0 -16 q6 -8 0 -16"/>
        <path d="M212 270 q-6 -8 0 -16 q6 -8 0 -16"/>
        <path d="M222 272 q-6 -8 0 -16 q6 -8 0 -16"/>
    </g>

    <!-- balon chat barista -->
    <g class="ill-bubble ill-bubble-a">
        <path d="M30 96 h92 a20 20 0 0 1 20 20 v6 a20 20 0 0 1 -20 20 h-58 l-14 14 v-14 h-20 a20 20 0 0 1 -20 -20 v-6 a20 20 0 0 1 20 -20 z" fill="#FFFDF8" stroke="#E0D5C1" stroke-width="2"/>
        <text x="76" y="125" text-anchor="middle" font-family="Plus Jakarta Sans, sans-serif" font-size="15" font-weight="700" fill="#2D1B0A">Gula aren?</text>
    </g>

    <!-- balon chat pelanggan -->
    <g class="ill-bubble ill-bubble-b">
        <path d="M262 100 h92 a20 20 0 0 1 20 20 v6 a20 20 0 0 1 -20 20 h-20 v14 l-14 -14 h-58 a20 20 0 0 1 -20 -20 v-6 a20 20 0 0 1 20 -20 z" fill="#A9471F"/>
        <g fill="#FFFDF8">
            <circle class="ill-dot" cx="290" cy="123" r="4.5"/>
            <circle class="ill-dot" cx="308" cy="123" r="4.5"/>
            <circle class="ill-dot" cx="326" cy="123" r="4.5"/>
        </g>
    </g>
</svg>
