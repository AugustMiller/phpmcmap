<?xml version="1.0" standalone="no"?>
<svg width="256" height="256" version="1.1" xmlns="http://www.w3.org/2000/svg">
    <style>
        .ground {
            fill: rgb(50, 50, 50);
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

    <text
        x="10"
        y="25"
        class="error">Error!</text>

    <text
        x="10"
        y="40"
        class="message">{{ $message }}</text>
</svg>
