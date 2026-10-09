import * as t from 'io-ts';

export class Tag {
    /** A tag as the API returns it. Tags embedded in a project come without their ordinal. */
    public static readonly codec = t.intersection([
        t.type({
            id: t.string,
            name: t.string
        }),
        t.partial({
            ordinal: t.number
        })
    ]);

    public constructor(
        public readonly id: string,
        public readonly name: string,
        public readonly ordinal: number | null = null
    ) {
    }

    public static fromJson(json: t.TypeOf<typeof Tag.codec>): Tag {
        return new Tag(json.id, json.name, json.ordinal ?? null);
    }
}
