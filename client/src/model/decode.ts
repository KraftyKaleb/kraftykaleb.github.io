import * as t from 'io-ts';
import {isLeft} from 'fp-ts/Either';
import {PathReporter} from 'io-ts/PathReporter';

/** Checks that a value matches a codec, throwing with each mismatched property if it does not. */
export function decode<A, O>(codec: t.Type<A, O>, value: unknown): A {
    const result = codec.decode(value);
    if (isLeft(result)) {
        throw new TypeError(PathReporter.report(result).join('\n'));
    }
    return result.right;
}
