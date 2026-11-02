/*
 * Spread items over `n` columns, always putting the next item in the shortest column.
 * ratios are width/height; an item's height is proportional to 1/ratio (+ a small gap allowance).
 * Equal-sized items therefore fill row by row (3 per row), and no column stays empty.
 *
 * @param {number[]} ratios
 * @param {number} n column count
 * @returns {number[][]} item indexes per column, in display order
 */
export function distribute(ratios, n, gap = 0.06) {
    const cols = Array.from({ length: n }, () => []);
    const heights = Array(n).fill(0);
    ratios.forEach((r, i) => {
        let k = 0;
        for (let j = 1; j < n; j++) if (heights[j] < heights[k] - 1e-9) k = j;
        cols[k].push(i);
        heights[k] += 1 / (r > 0 ? r : 4 / 3) + gap;
    });
    return cols;
}
