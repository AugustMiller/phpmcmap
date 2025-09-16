import { createSymbolIcon } from './util';

export default function createSignMarker(entity) {
    const lines = entity.metadata.front_text.messages
        .filter(l => l.length);

    const marker = L.marker([-entity.z, entity.x], {
        title: lines.join(' / '),
        icon: createSymbolIcon('sign'),
    });

    marker.bindPopup(lines.join('<br>'));

    return marker;
};
