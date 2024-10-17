import { createSymbolIcon } from './util';

export default function createChestMarker(entity) {
    if (typeof entity.metadata.Items === 'undefined') {
        throw new Error('Not enough metadata present to render.');
    }

    const total = entity.metadata.Items.reduce(function(total, slot) {
        const count = slot.count || slot.Count;

        return total + count;
    }, 0);

    const marker = L.marker([-entity.z, entity.x], {
        title: `Chest`,
        icon: createSymbolIcon('chest'),
    });

    marker.bindPopup(`Chest: ${total} item(s)`);

    return marker;
};
