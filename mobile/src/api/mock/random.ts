/** Seeded PRNG (mulberry32) so mock data is stable across reloads. */
export function createRandom(seed: number) {
  let state = seed >>> 0;

  const next = () => {
    state = (state + 0x6d2b79f5) >>> 0;
    let t = state;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };

  return {
    next,
    int(min: number, max: number) {
      return min + Math.floor(next() * (max - min + 1));
    },
    chance(probability: number) {
      return next() < probability;
    },
    pick<T>(items: readonly T[]): T {
      return items[Math.floor(next() * items.length)];
    },
    uuid() {
      const hex = () => Math.floor(next() * 16).toString(16);
      const block = (n: number) => Array.from({ length: n }, hex).join('');
      return `${block(8)}-${block(4)}-4${block(3)}-a${block(3)}-${block(12)}`;
    },
  };
}

export type Random = ReturnType<typeof createRandom>;
