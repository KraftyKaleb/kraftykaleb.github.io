import {Link} from "@app/model/link";

export class Project {

    public constructor(
        public readonly name: string,
        public readonly description: string,
        public readonly tags: string[],
        public readonly links: Link[] | null
    ) {
    }
}