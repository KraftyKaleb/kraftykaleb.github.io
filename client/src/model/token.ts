import * as t from 'io-ts';

export class Token {
    /** A bearer access token as PUT /api/token returns it. */
    public static readonly codec = t.type({
        token: t.string,
        expiresAt: t.string,
        username: t.string
    });

    public constructor(
        public readonly token: string,
        public readonly expiresAt: Date,
        public readonly username: string
    ) {
    }

    public static fromJson(json: t.TypeOf<typeof Token.codec>): Token {
        return new Token(json.token, new Date(json.expiresAt), json.username);
    }

    public get expired(): boolean {
        return this.expiresAt.getTime() <= Date.now();
    }
}
