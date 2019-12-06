import {api} from "@yosmy/request";
import {Platform} from "@yosmy/simple-ui";
import uniq from "lodash/uniq";
import uniqWith from "lodash/uniqWith";
import union from "lodash/union";
import unionBy from "lodash/unionBy";

const server = __DEV__ ? 'http://192.168.1.14' : 'https://api.prod.com';

const hash = (str) => {
    let hash = 0, i, chr;
    
    if (str.length === 0) { 
        return hash;
    }
    
    for (i = 0; i < str.length; i++) {
        chr = str.charCodeAt(i);
    
        hash = ((hash << 5) - hash) + chr;
    
        hash |= 0; // Convert to 32bit integer
    }
    
    return hash;
};


const Api = {
    addUser: (
        firstname,
        lastname,
        onReturn,
        onUserInvalidFirstnameException,
        onUserInvalidLastnameException,
        onConnectionException,
        onServerException,
        onUnknownException
    ) => {
        const resolve = (response, onReturn, onUserInvalidFirstnameException, onUserInvalidLastnameException, onUnknownException) => {
            const {code, payload} = response;
        
            switch (code) {
                case 'return':
                    onReturn(payload);
        
                    break;
                case 'user.invalid-firstname-exception':
                    onUserInvalidFirstnameException(payload);
                    
                    break;
                case 'user.invalid-lastname-exception':
                    onUserInvalidLastnameException(payload);
                    
                    break;
                default:
                    onUnknownException(response);
            }
        };
        
        api(
            server + '/add-user',
            null,
            null,
            {
                firstname: firstname,
                lastname: lastname
            }
            
        )
            .then((response) => {
                resolve(response, onReturn, onUserInvalidFirstnameException, onUserInvalidLastnameException, onUnknownException);    
            })
            .catch((response) => {
                const {code} = response;
                
                switch (code) {
                    case 'connection':
                        onConnectionException();
                    
                        break;
                    case 'server':
                        onServerException();
                    
                        break;
                    default:
                        onUnknownException(response);
                }
            });
        
    },
    collectUpdatedUsers: (
        token,
        ids,
        onReturn,
        onConnectionException,
        onServerException,
        onUnknownException
    ) => {
        const resolve = (response, onReturn, onUnknownException) => {
            const {code, payload} = response;
        
            switch (code) {
                case 'return':
                    onReturn(payload);
        
                    break;
                
                default:
                    onUnknownException(response);
            }
        };
        
        Platform.cache.get("/collect-updated-users")
            .then((file) => {
                const now = Date.now();
                
                Promise.all([
                    // Items with no cache
                    new Promise((resolve) => {
                        if (file) {
                            const {table} = file;
                    
                            // Just get ids with no cache
                            ids = ids.filter((id) => {
                                return table.indexOf(id) === -1
                            });
                            
                            if (ids.length === 0) {
                                // No need to call api
                                resolve([], []);
                                
                                return;
                            }
                        }
                        
                        
                        api(
                            server + '/collect-updated-users',
                            null,
                            token,
                            {
                                ids: ids,
                                updated: null
                            }
                            
                        )
                            .then((response) => {
                                resolve(ids, response.payload);    
                            })
                            .catch((response) => {
                                const {code} = response;
                                
                                switch (code) {
                                    case 'connection':
                                        onConnectionException();
                                    
                                        break;
                                    case 'server':
                                        onServerException();
                                    
                                        break;
                                    default:
                                        onUnknownException(response);
                                }
                            });
                        
                    }),
                    // Items with cache
                    new Promise((resolve) => {
                        if (!file) {
                            resolve([], []);
                        }
                        
                        let {date, table, response} = file;
        
                        // Cache not expired?
                        if (date + 60000 >= now) {
                            resolve(table, response.payload);
                            
                            return;
                        }
                        
                        
                        api(
                            server + '/collect-updated-users',
                            null,
                            token,
                            {
                                ids: table,
                                updated: date + 60000
                            }
                            
                        )
                            .then((response) => {
                                resolve(table, response.payload);    
                            })
                            .catch((response) => {
                                const {code} = response;
                                
                                switch (code) {
                                    case 'connection':
                                        onConnectionException();
                                    
                                        break;
                                    case 'server':
                                        onServerException();
                                    
                                        break;
                                    default:
                                        onUnknownException(response);
                                }
                            });
                        
                    }),
                ])
                    .then((result) => {
                        let table = union(
                            result[0][0],
                            result[1][0]
                        );
                        
                        let payload = unionBy(
                            result[0][1],
                            result[1][1],
                            "id"
                        );
                        
                        // Remove duplicated
                        
                        table = uniq(table);
                        
                        payload = uniqWith(
                            payload,
                            (a, b) => {
                                return a.id === b.id;
                            }
                        );
                        
                        const response = {
                            code: "return",
                            payload: payload
                        };
                        
                        Platform.cache.set(`/collect-updated-users`, {table: table, response: response, date: now}).catch(console.log);
                        
                        resolve(response, onReturn, onUnknownException);
                    });
            });
        
    },
    collectUsers: (
        token,
        ids,
        onReturn,
        onConnectionException,
        onServerException,
        onUnknownException
    ) => {
        const resolve = (response, onReturn, onUnknownException) => {
            const {code, payload} = response;
        
            switch (code) {
                case 'return':
                    onReturn(payload);
        
                    break;
                
                default:
                    onUnknownException(response);
            }
        };
        
        Platform.cache.get("/collect-users")
            .then((file) => {
                const now = Date.now();
                
                Promise.all([
                    // Items with no cache
                    new Promise((resolve) => {
                        if (file) {
                            const {table} = file;
                    
                            // Just get ids with no cache
                            ids = ids.filter((id) => {
                                return table.indexOf(id) === -1
                            });
                            
                            if (ids.length === 0) {
                                // No need to call api
                                resolve([], []);
                                
                                return;
                            }
                        }
                        
                        
                        api(
                            server + '/collect-users',
                            null,
                            token,
                            {
                                ids: ids
                            }
                            
                        )
                            .then((response) => {
                                resolve(ids, response.payload);    
                            })
                            .catch((response) => {
                                const {code} = response;
                                
                                switch (code) {
                                    case 'connection':
                                        onConnectionException();
                                    
                                        break;
                                    case 'server':
                                        onServerException();
                                    
                                        break;
                                    default:
                                        onUnknownException(response);
                                }
                            });
                        
                    }),
                    // Items with cache
                    new Promise((resolve) => {
                        if (!file) {
                            resolve([], []);
                        }
                        
                        let {date, table, response} = file;
        
                        // Cache not expired?
                        if (date + 60000 >= now) {
                            resolve(table, response.payload);
                            
                            return;
                        }
                        
                        
                        api(
                            server + '/collect-users',
                            null,
                            token,
                            {
                                ids: table
                            }
                            
                        )
                            .then((response) => {
                                resolve(table, response.payload);    
                            })
                            .catch((response) => {
                                const {code} = response;
                                
                                switch (code) {
                                    case 'connection':
                                        onConnectionException();
                                    
                                        break;
                                    case 'server':
                                        onServerException();
                                    
                                        break;
                                    default:
                                        onUnknownException(response);
                                }
                            });
                        
                    }),
                ])
                    .then((result) => {
                        let table = union(
                            result[0][0],
                            result[1][0]
                        );
                        
                        let payload = unionBy(
                            result[0][1],
                            result[1][1],
                            "id"
                        );
                        
                        // Remove duplicated
                        
                        table = uniq(table);
                        
                        payload = uniqWith(
                            payload,
                            (a, b) => {
                                return a.id === b.id;
                            }
                        );
                        
                        const response = {
                            code: "return",
                            payload: payload
                        };
                        
                        Platform.cache.set(`/collect-users`, {table: table, response: response, date: now}).catch(console.log);
                        
                        resolve(response, onReturn, onUnknownException);
                    });
            });
        
    },
    pickUser: (
        token,
        id,
        onReturn,
        onConnectionException,
        onServerException,
        onUnknownException
    ) => {
        const resolve = (response, onReturn, onUnknownException) => {
            const {code, payload} = response;
        
            switch (code) {
                case 'return':
                    onReturn(payload);
        
                    break;
                
                default:
                    onUnknownException(response);
            }
        };
        
        Platform.cache.get(`/pick-user-${hash(id)}`)
            .then((file) => {
                if (file) {
                    const {response} = file;
                        
                    resolve(response, onReturn, onUnknownException);
                    
                    return;
                }
                
                
        api(
            server + '/pick-user',
            null,
            token,
            {
                id: id
            }
            
        )
            .then((response) => {
                
                Platform.cache.set(`/pick-user-${hash(id)}`, {response: response}).catch(console.log);
                
                resolve(response, onReturn, onUnknownException);    
            })
            .catch((response) => {
                const {code} = response;
                
                switch (code) {
                    case 'connection':
                        onConnectionException();
                    
                        break;
                    case 'server':
                        onServerException();
                    
                        break;
                    default:
                        onUnknownException(response);
                }
            });
        
            });
        
    },
    removeUser: (
        id,
        onReturn,
        onConnectionException,
        onServerException,
        onUnknownException
    ) => {
        const resolve = (response, onReturn, onUnknownException) => {
            const {code, payload} = response;
        
            switch (code) {
                case 'return':
                    onReturn(payload);
        
                    break;
                
                default:
                    onUnknownException(response);
            }
        };
        
        api(
            server + '/remove-user',
            null,
            null,
            {
                id: id
            }
            
        )
            .then((response) => {
                Platform.cache.delete(`/pick-user-${hash(id)}`).catch(console.log);
                
                resolve(response, onReturn, onUnknownException);    
            })
            .catch((response) => {
                const {code} = response;
                
                switch (code) {
                    case 'connection':
                        onConnectionException();
                    
                        break;
                    case 'server':
                        onServerException();
                    
                        break;
                    default:
                        onUnknownException(response);
                }
            });
        
    },
    user: {
        updateFirstname: (
            id,
            firstname,
            onReturn,
            onConnectionException,
            onServerException,
            onUnknownException
        ) => {
            const resolve = (response, onReturn, onUnknownException) => {
                const {code, payload} = response;
            
                switch (code) {
                    case 'return':
                        onReturn(payload);
            
                        break;
                    
                    default:
                        onUnknownException(response);
                }
            };
            
            api(
                server + '/user/update-firstname',
                null,
                null,
                {
                    id: id,
                    firstname: firstname
                }
                
            )
                .then((response) => {
                    resolve(response, onReturn, onUnknownException);    
                })
                .catch((response) => {
                    const {code} = response;
                    
                    switch (code) {
                        case 'connection':
                            onConnectionException();
                        
                            break;
                        case 'server':
                            onServerException();
                        
                            break;
                        default:
                            onUnknownException(response);
                    }
                });
            
        }
    }
};

const WrappedApi = (
    session, 
    token, 
    onConnectionException,
    onServerException,
    onUnknownException
) => {
    return {
        addUser: (
            ...props
        ) => {
            Api.addUser(
                ...props,
                onConnectionException,
                onServerException,
                onUnknownException
            )
        },
        collectUpdatedUsers: (
            ...props
        ) => {
            Api.collectUpdatedUsers(
                token,
                ...props,
                onConnectionException,
                onServerException,
                onUnknownException
            )
        },
        collectUsers: (
            ...props
        ) => {
            Api.collectUsers(
                token,
                ...props,
                onConnectionException,
                onServerException,
                onUnknownException
            )
        },
        pickUser: (
            ...props
        ) => {
            Api.pickUser(
                token,
                ...props,
                onConnectionException,
                onServerException,
                onUnknownException
            )
        },
        removeUser: (
            ...props
        ) => {
            Api.removeUser(
                ...props,
                onConnectionException,
                onServerException,
                onUnknownException
            )
        },
        user: {
            updateFirstname: (
                ...props
            ) => {
                Api.user.updateFirstname(
                    ...props,
                    onConnectionException,
                    onServerException,
                    onUnknownException
                )
            }
        }
    };
};

export default WrappedApi;