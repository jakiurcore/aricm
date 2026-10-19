<section class="bg-green-700 py-10 text-white">
    <div class="mx-auto grid max-w-page grid-cols-2 gap-6 px-4 text-center md:grid-cols-4">
        @foreach ($stats as $stat)
            <div><p class="font-head text-4xl">{{ $stat['value'] }}</p><p class="text-white/85">{{ $stat['label'] }}</p></div>
        @endforeach
    </div>
</section>
