import React from 'react'

export class Menu extends React.Component {
    Menu(props, state) {
        let container = [];
        for (let key in props) {
            container.push(<li className={key == state ? 'active' : ''}><a href={'javascript:;'}
                                                                           onClick={this.onClickMenu.bind(this, key)}>{props[key]}</a>
            </li>)
        }
        return <div className={'navbar navbar-default'}>
            <div className={'container'}>
                <div className={'navbar-collapse collapse'}>
                    <ul className={'nav navbar-nav'}>{container}</ul>
                </div>
            </div>
        </div>
    }

    onClickMenu(props) {
        window.history.pushState('', 'title', window.location.href.replace(/#.*/, '') + '#' + props);
        this.setState({menu: props})

    }
}
