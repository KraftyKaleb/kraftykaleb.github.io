import {Link} from "@app/model/link";

export type ProjectCategory = 'professional' | 'side' | 'other';

export class Project {

    public constructor(
        public readonly name: string,
        public readonly description: string,
        public readonly category: ProjectCategory,
        public readonly tags: string[],
        public readonly links: Link[] | null
    ) {
    }
}
