import { cleanEntityId, createSymbolMarker } from './util';

export default function createMobSpawnerMarker(entity) {
    return createSymbolMarker(entity, `Mob spawner (${cleanEntityId(entity.metadata.SpawnData.entity.id)})`)
};
