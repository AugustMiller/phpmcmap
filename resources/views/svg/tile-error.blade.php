<?xml version="1.0" standalone="no"?>
<svg width="256" height="256" version="1.1" xmlns="http://www.w3.org/2000/svg">
    <style>
        .ground {
            fill: rgb(50, 50, 50);
        }

        .border {
            fill: none;
            stroke: rgb(65, 65, 65);
            stroke-width: 1px;
        }

        .x {
            stroke: rgb(100, 100, 100);
            stroke-width: 1px;
        }

        .error {
            fill: rgb(140, 140, 140);
            font-family: monospace;
            font-size: 10px;
            font-weight: bold;
        }

        .message {
            fill: rgb(100, 100, 100);
            font-family: monospace;
            font-size: 10px;
        }
    </style>

    <rect
        x="0"
        y="0"
        width="256"
        height="256"
        class="ground"></rect>

    <rect
        x="5"
        y="5"
        width="246"
        height="246"
        class="border"></rect>

    <line x1="100" y1="100" x2="156" y2="156" class="x" />
    <line x1="156" y1="100" x2="100" y2="156" class="x" />

    <text
        x="15"
        y="25"
        class="error">Uh oh!</text>

    <text
        x="15"
        y="40"
        class="message">{{ $message }}</text>
</svg>
