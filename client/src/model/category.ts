import * as t from 'io-ts';

export class Category {
    /** A category as the API returns it. */
    public static readonly codec = t.type({
        id: t.string,
        name: t.string,
        description: t.string
    });

    public constructor(
        public readonly id: string,
        public readonly name: string,
        public readonly description: string
    ) {
    }

    public static fromJson(json: t.TypeOf<typeof Category.codec>): Category {
        return new Category(json.id, json.name, json.description);
    }
}
