import * as t from 'io-ts';

export class Link {
    /** A link as the API returns it. */
    public static readonly codec = t.type({
        title: t.string,
        url: t.string,
        icon: t.string
    });

    public constructor(
        public title: string,
        public url: string,
        public icon: string
    ) {
    }

    public static fromJson(json: t.TypeOf<typeof Link.codec>): Link {
        return new Link(json.title, json.url, json.icon);
    }
}
