<?xml version="1.0" standalone="no"?>
<svg width="256" height="256" version="1.1" xmlns="http://www.w3.org/2000/svg">
    <title>{{ $title }}</title>

    <rect
        x="0"
        y="0"
        width="256"
        height="256"
        class="ground"
        fill="rgb(50, 50, 50)"></rect>

    @foreach ($rects as $rect)
        <rect
            x="{{ $rect['x'] }}"
            y="{{ $rect['y'] }}"
            width="{{ $rect['width'] }}"
            height="{{ $rect['height'] }}"
            fill="{{ $rect['color'] }}"></rect>
    @endforeach
</svg>
