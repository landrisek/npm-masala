export class Container {
    static Inject(..._bases) {
        class classes {
            constructor(..._args) {
                var index = 0;
                for (let b of this.base) {
                    let obj = new b(_args[index++]);
                    Container.copy(this, obj);
                }
            }

            get base() {
                return _bases;
            }
        }

        for (let base of _bases) {
            Container.copy(classes, base);
            Container.copy(classes.prototype, base.prototype);
        }
        return classes;
    }

    static copy(_target, _source) {
        for (let key of Reflect.ownKeys(_source)) {
            if ('constructor' !== key && 'prototype' !== key && 'name' !== key) {
                let desc = Object.getOwnPropertyDescriptor(_source, key);
                Object.defineProperty(_target, key, desc);
            }
        }
    }
}
