import { cleanEntityId } from '../entities/util';
import createSignMarker from '../entities/sign';
import createChestMarker from '../entities/chest';
import createMobSpawnerMarker from '../entities/mob-spawner';
import { createSymbolMarker } from '../entities/util';

const entityMap = {
    barrel: createChestMarker,
    bed: createSymbolMarker,
    bell: createSymbolMarker,
    campfire: createSymbolMarker,
    chest: createChestMarker,
    mob_spawner: createMobSpawnerMarker,
    sign: createSignMarker,
};

export function refreshPoi(map, layer) {
    getPoi(map.getBounds())
        .then(function(entities) {
            return clearPoi(entities, layer);
        })
        .then(function(entities) {
            return addPoi(entities, layer);
        });
};

export function getPoi(bounds) {
    const nw = bounds.getNorthWest();
    const se = bounds.getSouthEast();

    return fetch(`/api/poi/${Math.ceil(nw.lng)},${-Math.ceil(nw.lat)}/${Math.floor(se.lng)},${-Math.floor(se.lat)}`)
        .then(function(res) {
            return res.json();
        });
};

export function addPoi(entities, layer) {
    entities.forEach(function(entity) {
        const type = cleanEntityId(entity.entity_type);
        let marker = null;

        try {
            marker = entityMap[type](entity);
        } catch (e) {
            console.error(`Skipping unsupported entity: ${type}`);

            return;
        }

        marker.__MC_ENTITY_ID__ = entity.id;

        layer.addLayer(marker);
    });
};

export function clearPoi(entities, layer) {
    const newIds = entities.map(p => p.id);
    const existingIds = layer.getLayers().map(p => p.__MC_ENTITY_ID__);

    // Remove anything that is no longer in view:
    layer.eachLayer(function(p) {
        if (!newIds.includes(p.__MC_ENTITY_ID__)) {
            layer.removeLayer(p);
        }
    });

    // Filter out any entities already on the map:
    return entities.filter(function(e) {
        return !existingIds.includes(e.id);
    });
};
