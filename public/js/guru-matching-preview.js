/* Matching preview state shared by the Guru question detail and interactive preview. */
(function (global) {
    function normalizePairs(pairData) {
        return Array.isArray(pairData)
            ? pairData.filter(pair => pair && pair.left_id != null && pair.right_id != null)
            : Object.entries(pairData || {}).flatMap(([leftId, rightIds]) =>
                (Array.isArray(rightIds) ? rightIds : [rightIds])
                    .filter(rightId => rightId != null)
                    .map(rightId => ({ left_id: leftId, right_id: rightId }))
            );
    }

    function isFullyAligned(leftItems, rightItems, pairs) {
        if (leftItems.length < 2 || rightItems.length < 2) return false;
        const rightPositions = new Map(
            rightItems.map((item, index) => [String(item.id), index])
        );
        return leftItems.every((item, index) =>
            pairs.some(pair =>
                String(pair.left_id) === String(item.id)
                && rightPositions.get(String(pair.right_id)) === index
            )
        );
    }

    function shuffle(items, random) {
        for (let index = items.length - 1; index > 0; index--) {
            const targetIndex = Math.floor(random() * (index + 1));
            [items[index], items[targetIndex]] = [items[targetIndex], items[index]];
        }
    }

    function createState(targets, leftItems, pairData, random = Math.random) {
        const pairs = normalizePairs(pairData);
        const rightOrder = [...targets];
        if (rightOrder.length > 1) {
            let attempts = 0;
            do {
                shuffle(rightOrder, random);
                attempts++;
            } while (attempts < 20 && isFullyAligned(leftItems, rightOrder, pairs));

            if (isFullyAligned(leftItems, rightOrder, pairs)) {
                const swaps = [];
                for (let left = 0; left < rightOrder.length; left++) {
                    for (let right = left + 1; right < rightOrder.length; right++) {
                        const candidate = [...rightOrder];
                        [candidate[left], candidate[right]] = [candidate[right], candidate[left]];
                        if (!isFullyAligned(leftItems, candidate, pairs)) {
                            swaps.push([left, right]);
                        }
                    }
                }
                if (swaps.length) {
                    const [left, right] = swaps[Math.floor(random() * swaps.length)];
                    [rightOrder[left], rightOrder[right]] = [rightOrder[right], rightOrder[left]];
                }
            }
        }

        return {
            rightOrder,
            answers: [],
            selectedLeftId: null
        };
    }

    function drawLines(board, svg, pairData, leftAttribute, rightAttribute, dotClass, lineClass) {
        const rect = board.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        svg.setAttribute('viewBox', `0 0 ${rect.width} ${rect.height}`);
        svg.setAttribute('width', String(rect.width));
        svg.setAttribute('height', String(rect.height));
        svg.replaceChildren();

        normalizePairs(pairData).forEach(({ left_id: leftId, right_id: rightId }) => {
            const leftDot = board.querySelector(
                `[data-${leftAttribute}="${CSS.escape(String(leftId))}"] .${dotClass}`
            );
            const rightDot = board.querySelector(
                `[data-${rightAttribute}="${CSS.escape(String(rightId))}"] .${dotClass}`
            );
            if (!leftDot || !rightDot) return;
            const leftRect = leftDot.getBoundingClientRect();
            const rightRect = rightDot.getBoundingClientRect();
            const line = global.document.createElementNS(
                'http://www.w3.org/2000/svg',
                'line'
            );
            line.classList.add(lineClass);
            line.setAttribute('x1', String(leftRect.left + leftRect.width / 2 - rect.left));
            line.setAttribute('y1', String(leftRect.top + leftRect.height / 2 - rect.top));
            line.setAttribute('x2', String(rightRect.left + rightRect.width / 2 - rect.left));
            line.setAttribute('y2', String(rightRect.top + rightRect.height / 2 - rect.top));
            svg.appendChild(line);
        });
    }

    global.SaluangMatchingPreview = { createState, drawLines };
})(window);
