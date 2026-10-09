import * as t from 'io-ts';

export class Skill {
    /** A skill as the API returns it. */
    public static readonly codec = t.type({
        id: t.string,
        name: t.string
    });

    public constructor(
        public readonly id: string,
        public readonly name: string
    ) {
    }

    public static fromJson(json: t.TypeOf<typeof Skill.codec>): Skill {
        return new Skill(json.id, json.name);
    }
}
