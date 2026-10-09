import * as t from 'io-ts';
import {Link} from "@app/model/link";
import {Tag} from "@app/model/tag";

export class Project {
    /** A project as the API returns it. */
    public static readonly codec = t.type({
        id: t.string,
        name: t.string,
        description: t.string,
        category: t.type({id: t.string}),
        ordinal: t.number,
        tags: t.array(Tag.codec),
        links: t.array(Link.codec)
    });

    public constructor(
        public readonly id: string,
        public readonly name: string,
        public readonly description: string,
        public readonly categoryId: string,
        public readonly ordinal: number,
        public readonly tags: Tag[],
        public readonly links: Link[]
    ) {
    }

    public static fromJson(json: t.TypeOf<typeof Project.codec>): Project {
        return new Project(
            json.id,
            json.name,
            json.description,
            json.category.id,
            json.ordinal,
            json.tags.map(Tag.fromJson),
            json.links.map(Link.fromJson)
        );
    }
}
