import React, {Component} from 'react';
import ReactDOM from 'react-dom';

export default class Row extends Component {
	
	render() {
		var container = []
		for(var td in this.props.columns) {
			container.push(<Cell key={td} attributes={this.props.columns[td].attributes} value={this.props.columns[td].value} />)
		}
		return <tr>{container}</tr>
	}
}

<Row key=key columns=this.state.rows[key]>