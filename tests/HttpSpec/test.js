const { describe, test } = require('@jest/globals');
const { serverAudits } = require('graphql-http');
const fetch = require('node-fetch');

describe('graphql-http server audits', () => {
    const audits = serverAudits({
        url: () => 'http://server/graphql',
        fetchFn: fetch,
    });

    for (const audit of audits) {
        test(`[${audit.id}] ${audit.name}`, async () => {
            const result = await audit.fn();

            if (result.status === 'ok') {
                return;
            }

            if (result.status !== 'warn' && result.status !== 'notice' && result.status !== 'error') {
                throw new Error(`unknown status ${result.status}`);
            }

            throw new Error(`${result.reason}. Response: [${result.response?.status}] ` + (await result.response?.text()));
        });
    }
});
