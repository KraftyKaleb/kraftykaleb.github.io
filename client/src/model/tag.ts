import * as t from 'io-ts';

export class Tag {
    /** A tag as the API returns it. */
    public static readonly codec = t.type({
        id: t.string,
        name: t.string
    });

    public constructor(
        public readonly id: string,
        public readonly name: string
    ) {
    }

    public static fromJson(json: t.TypeOf<typeof Tag.codec>): Tag {
        return new Tag(json.id, json.name);
    }
}
